<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Concerns\AuthorizesSppdAccess;
use App\Enums\DepartmentType;
use App\Enums\EmployeeType;
use App\Enums\SignatureDocumentType;
use App\Enums\SignatureStatus;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Helpers\QrSimulator;
use App\Helpers\Terbilang;
use App\Jobs\SendTteSignRequestJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Budget;
use App\Models\Department;
use App\Models\Province;
use App\Models\Regency;
use App\Models\SppdApproval;
use App\Models\SppdCategory;
use App\Models\SppdDigitalSignature;
use App\Models\SppdRequest;
use App\Models\SppdWorkflow;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;
use Vinkla\Hashids\Facades\Hashids;

class SppdController extends Controller
{
  use AuthorizesSppdAccess;

  public function index(Request $request): View
  {
    if (! $request->has('jabatan')) {
      $user = Auth::user();
      $isDprd = $user->department?->type?->value === 'dprd' || $user->department?->parent?->type?->value === 'dprd';
      $isSuperAdmin = $user->hasRole('super_admin');

      $defaultJabatan = ($isSuperAdmin || $isDprd) ? 'anggota_dprd' : 'kepala_opd';
      $request->merge(['jabatan' => $defaultJabatan]);
    }

    $query = SppdRequest::with(['user', 'category', 'budget.department']);

    // Filter by department hierarchy (Option B)
    if (! Auth::user()->hasRole('super_admin')) {
      $dept = Auth::user()->department;
      if ($dept) {
        $allowedIds = $dept->getAllRelatedIds();
        $query->whereHas('user', function ($q) use ($allowedIds) {
          $q->whereIn('department_id', $allowedIds);
        });
      } else {
        $query->whereHas('user', function ($q) {
          $q->where('department_id', Auth::user()->department_id);
        });
      }
    }

    // Filter by status
    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    // Filter by domain
    if ($request->filled('domain')) {
      $query->where('domain', $request->domain);
    }

    // Filter: show only pending approvals for current user
    if ($request->filter === 'approval') {
      $pendingSppdIds = SppdApproval::where('approver_id', Auth::id())
        ->where('status', ApprovalStatus::PENDING)
        ->pluck('sppd_request_id');
      $query->whereIn('id', $pendingSppdIds);
    }

    // Filter by Jabatan/Eselon
    if ($request->filled('jabatan')) {
      $jabatan = $request->jabatan;
      $query->whereHas('user', function ($q) use ($jabatan) {
        if ($jabatan === 'kepala_opd') {
          $q->role('kepala_opd');
        } elseif ($jabatan === 'eselon_ii') {
          $q->role(['sekda', 'asisten', 'kepala_opd', 'sekwan']);
        } elseif ($jabatan === 'eselon_staf') {
          $q->role(['staf', 'admin_opd', 'sekretaris_opd', 'kasubid_kasubag', 'kabid_irban_kabag']);
        } elseif ($jabatan === 'eselon_iv') {
          $q->role(['kasubid_kasubag', 'sekcam', 'lurah', 'kapus']);
        } elseif ($jabatan === 'staf') {
          $q->role('staf');
        } elseif ($jabatan === 'anggota_dprd') {
          $q->where(function ($sub) {
            $sub->role(['anggota_dprd', 'pimpinan_dprd'])
              ->orWhere('employee_type', EmployeeType::DPRD);
          });
        } elseif ($jabatan === 'staff_dprd') {
          $q->role('staf')->whereHas('department', function ($d) {
            $d->where(function ($sub) {
              $sub->where('type', DepartmentType::DPRD)
                ->orWhereHas('parent', function ($p) {
                  $p->where('type', DepartmentType::DPRD);
                });
            });
          });
        } elseif ($jabatan === 'sekwan') {
          $q->role('sekwan');
        }
      });
    }

    // Search
    if ($request->filled('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('purpose', 'like', "%{$search}%")
          ->orWhere('document_number', 'like', "%{$search}%")
          ->orWhereHas('user', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
      });
    }

    $sppds = $query->latest()->paginate(15)->withQueryString();
    $statuses = SppdStatus::cases();
    $domains = SppdDomain::cases();

    return view('sppd.index', compact('sppds', 'statuses', 'domains'));
  }

  /**
   * Portal 'Selanjutnya' (Image 2)
   */
  public function next(SppdRequest $sppd)
  {
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Halaman ini belum dapat diakses karena pengajuan SPPD belum disetujui sepenuhnya.');

    $sppd->load(['user', 'followers.user']);

    return view('sppd.next', compact('sppd'));
  }

  /**
   * Halaman Kelola SPPD (Image 1)
   */
  public function manageSppd(SppdRequest $sppd)
  {
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Halaman ini belum dapat diakses karena pengajuan SPPD belum disetujui sepenuhnya.');

    $sppd->load(['user', 'followers.user']);

    return view('sppd.manage_sppd', compact('sppd'));
  }

  /**
   * Halaman Kelola SPT (Image 2)
   */
  public function manageSpt(SppdRequest $sppd)
  {
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Halaman ini belum dapat diakses karena pengajuan SPPD belum disetujui sepenuhnya.');

    $sppd->load(['user']);

    return view('sppd.manage_spt', compact('sppd'));
  }

  /**
   * Halaman Kuitansi (Image 3)
   */
  public function receipts(SppdRequest $sppd)
  {
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Halaman ini belum dapat diakses karena pengajuan SPPD belum disetujui sepenuhnya.');

    $sppd->load(['user.department', 'budget.department', 'followers.user', 'advanceReceipts', 'actualExpenses', 'costDetails']);

    // Cari bendahara pengeluaran di OPD terkait berdasarkan jabatan (dengan fallback ke induk jika di sub-unit)
    $dept = $sppd->user->department;
    $bendahara = null;
    if ($dept) {
      $bendahara = User::whereHas('position', fn($q) => $q->where('name', 'Bendahara Pengeluaran'))
        ->where('department_id', $dept->id)
        ->where('is_active', true)
        ->first();

      if (! $bendahara && $dept->parent_id) {
        $bendahara = User::whereHas('position', fn($q) => $q->where('name', 'Bendahara Pengeluaran'))
          ->where('department_id', $dept->parent_id)
          ->where('is_active', true)
          ->first();
      }
    }

    return view('sppd.costs.receipts', compact('sppd', 'bendahara'));
  }

  /**
   * Halaman Laporan Pengeluaran Rill (Image 4)
   */
  public function actualExpenses(SppdRequest $sppd)
  {
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Halaman ini belum dapat diakses karena pengajuan SPPD belum disetujui sepenuhnya.');

    $sppd->load(['user', 'pptk', 'followers.user', 'actualExpenses.user']);

    // Kandidat PPTK: pegawai di OPD yang sama (termasuk sub-unit)
    $dept = $sppd->user->department;
    $opdId = $dept->parent_id ?? $dept->id; // naik ke OPD induk jika di sub-unit

    // Kumpulkan ID OPD induk + semua sub-unit
    $deptIds = Department::where('id', $opdId)
      ->orWhere('parent_id', $opdId)
      ->pluck('id');

    $pptkCandidates = User::whereIn('department_id', $deptIds)
      ->where('is_active', true)
      ->orderBy('name')
      ->get(['id', 'name', 'nip']);

    return view('sppd.costs.actuals', compact('sppd', 'pptkCandidates'));
  }

  /**
   * Update PPTK pada SPPD
   */
  public function updatePptk(SppdRequest $sppd, Request $request)
  {
    abort_unless(Auth::user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Aksi ini tidak diizinkan.');
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');

    // PPTK harus pegawai aktif di lingkup OPD pemohon (induk + sub-unit) — sama dengan kandidat di actualExpenses().
    $dept = $sppd->user->department;
    $opdId = $dept?->parent_id ?? $dept?->id;
    $allowedDeptIds = Department::where('id', $opdId)
      ->orWhere('parent_id', $opdId)
      ->pluck('id')
      ->all();

    $request->validate([
      'pptk_id' => [
        'required',
        Rule::exists('users', 'id')
          ->where('is_active', true)
          ->whereIn('department_id', $allowedDeptIds),
      ],
    ]);

    $sppd->update(['pptk_id' => $request->pptk_id]);

    return back()->with('success', 'PPTK berhasil diperbarui.');
  }

  /**
   * Halaman Rincian Biaya Perjalanan Dinas (Image 5)
   */
  public function finalCosts(SppdRequest $sppd)
  {
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Halaman ini belum dapat diakses karena pengajuan SPPD belum disetujui sepenuhnya.');

    $sppd->load(['user.department', 'pptk', 'followers.user', 'costDetails.user']);

    // Cari bendahara pengeluaran di OPD terkait berdasarkan jabatan
    $bendahara = User::whereHas('position', fn($q) => $q->where('name', 'Bendahara Pengeluaran'))
      ->where('department_id', $sppd->user->department_id)
      ->where('is_active', true)
      ->first();

    return view('sppd.costs.final_details', compact('sppd', 'bendahara'));
  }

  /**
   * Halaman Input Laporan Perjalanan
   */
  public function reportInput(SppdRequest $sppd)
  {
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Halaman ini belum dapat diakses karena pengajuan SPPD belum disetujui sepenuhnya.');

    $sppd->load(['user', 'report']);

    return view('sppd.report_input', compact('sppd'));
  }

  /**
   * Store or update Laporan Perjalanan
   */
  public function storeReport(Request $request, SppdRequest $sppd)
  {
    abort_unless(Auth::user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Aksi ini tidak diizinkan.');
    $this->authorizeSppdAccess($sppd);
    abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');

    $hasReportFile = $sppd->report?->report_file;
    $hasDocFile = $sppd->report?->documentation_file;

    $validated = $request->validate([
      'report_date' => 'required|date',
      'report_file' => ($hasReportFile ? 'nullable' : 'required') . '|file|max:20480',
      'documentation_file' => ($hasDocFile ? 'nullable' : 'required') . '|image|max:20480',
    ]);

    if ($request->hasFile('report_file')) {
      if ($sppd->report?->report_file) {
        Storage::disk('public')->delete($sppd->report->report_file);
      }
      $validated['report_file'] = $request->file('report_file')
        ->store(date('Y') . '/laporan_perjalanan', 'public');
    }

    if ($request->hasFile('documentation_file')) {
      if ($sppd->report?->documentation_file) {
        Storage::disk('public')->delete($sppd->report->documentation_file);
      }
      $validated['documentation_file'] = $request->file('documentation_file')
        ->store(date('Y') . '/dokumentasi_perjalanan', 'public');
    }

    // Calculate total expense from actual expenses
    $validated['total_expense'] = $sppd->actualExpenses()->sum('amount');

    $sppd->report()->updateOrCreate(
      ['sppd_request_id' => $sppd->id],
      $validated
    );

    return back()->with('success', 'Laporan perjalanan berhasil disimpan.');
  }



  private function resolveSptApproval(SppdRequest $sppd)
  {
    $approval = $sppd->approvals()->where('signs_spt', true)->first();
    if ($approval) {
      return $approval;
    }

    // Fallback: last step
    return $sppd->approvals()->reorder('step_order', 'desc')->first();
  }

  private function resolveSppdApproval(SppdRequest $sppd, ?User $targetUser = null)
  {
    $approval = $sppd->approvals()->where('signs_sppd', true)->first();
    if ($approval) {
      return $approval;
    }

    // Fallback: existing custom logic
    $targetUser = $targetUser ?? $sppd->user;
    $isDprd = $targetUser->department?->type === DepartmentType::DPRD
      || $targetUser->department?->parent?->type === DepartmentType::DPRD;

    if ($isDprd) {
      $sppdApproval = $sppd->approvals()
        ->with('approver.roles')
        ->get()
        ->first(function ($approval) {
          return $approval->approver && $approval->approver->hasRole('sekwan');
        });
      if ($sppdApproval) {
        return $sppdApproval;
      }
    }

    $cityWideRoles = ['walikota', 'sekda', 'kepala_daerah'];
    $opdHeadApproval = $sppd->approvals()
      ->with('approver.roles')
      ->reorder('step_order', 'desc')
      ->get()
      ->first(function ($approval) use ($cityWideRoles) {
        return $approval->approver &&
          ! $approval->approver->roles->pluck('name')->intersect($cityWideRoles)->isNotEmpty();
      });

    return $opdHeadApproval ?? $sppd->approvals()->reorder('step_order', 'asc')->first();
  }



  /**
   * API: Get regencies by province
   */
  public function getRegencies(Province $province)
  {
    return response()->json($province->regencies()->orderBy('name')->get());
  }

  /**
   * API: Preview workflow for a specific user and destination
   */
  public function previewWorkflow(Request $request)
  {
    $request->validate([
      'user_id' => 'required|exists:users,id',
      'domain' => 'nullable|string',
    ]);

    $user = User::with('department.parent')->find($request->user_id);

    // Batasi preview ke pegawai dalam lingkup department yang diizinkan (super_admin bebas) —
    // mencegah enumerasi struktur organisasi oleh user login mana pun.
    $authUser = Auth::user();
    if (! $authUser->hasRole('super_admin')) {
      $dept = $authUser->department;
      $allowedIds = $dept ? $dept->getAllRelatedIds() : collect([$authUser->department_id]);
      abort_unless($allowedIds->contains($user->department_id), 403, 'Anda tidak memiliki akses ke data pegawai ini.');
    }

    $workflowService = app(SppdWorkflowService::class);

    $steps = $workflowService->simulateApprovals($user, $request->domain);

    return response()->json([
      'user' => [
        'name' => $user->name,
        'department' => $user->department?->name ?? 'Tanpa Unit Kerja',
        'role' => $user->getRoleNames()->first() ?? 'Tanpa Role',
        'role_label' => $user->roles->first()?->label ?? ($user->getRoleNames()->first() ?? 'Tanpa Role'),
      ],
      'has_header' => (bool) ($user->department?->getInheritedLetterhead() && Str::contains($user->department->getInheritedLetterhead(), '/')),
      'steps' => $steps,
    ]);
  }

  public function streamSpt(SppdRequest $sppd)
  {
    $this->authorizeSppdAccess($sppd);
    // Check if already TTE signed, if so stream the signed file
    $signature = $sppd->signatureFor('spt');
    if ($signature && $signature->status->value === SignatureStatus::SIGNED->value && $signature->signed_file_path) {
      $filePath = storage_path('app/public/' . $signature->signed_file_path);
      if (file_exists($filePath)) {
        return response()->file($filePath, [
          'Content-Type' => 'application/pdf',
          'Content-Disposition' => 'inline; filename="SPT-' . Str::slug($sppd->document_number ?: $sppd->id) . '.pdf"',
        ]);
      }
    }

    $sppd->load(['user.department', 'user.rank', 'budget', 'category', 'destinations.regency', 'followers.user.rank']);
    $sptApproval = $this->resolveSptApproval($sppd);
    $duration = Carbon::parse($sppd->start_date)->diffInDays(Carbon::parse($sppd->end_date)) + 1;

    $approver = $sptApproval?->approver;
    $approverRole = ($approver && $approver->isDprdMember() && $approver->dprd_jabatan)
      ? $approver->dprd_jabatan
      : ($approver?->position_name
        ?? $approver?->position?->name
        ?? $sptApproval?->role_label
        ?? 'Kepala Dinas');

    $pdfData = [
      'approver_name' => $approver->name ?? '................................',
      'approver_role' => $approverRole,
      'approver_nip' => $approver->nip ?? null,
      'approver_rank' => $approver->rank->name ?? '',
      'approver_group' => $approver->rank->group ?? '',
      'is_walikota' => $approver && $approver->hasRole('walikota'),
      'is_approved' => in_array($sppd->status, [SppdStatus::APPROVED, SppdStatus::COMPLETED]),
      'qr_image' => null,
      'duration' => $duration,
      'letterhead_url' => ($sppd->user->department?->getRootDepartment()?->type === DepartmentType::DPRD && $sppd->user->isDprdMember())
        ? $sppd->user->department->getInheritedLetterheadSecond()
        : $sppd->user->department?->getInheritedLetterhead(),
    ];

    if ($pdfData['is_approved'] && $sppd->isSigned('spt')) {
      $verifyUrl = url('/verify/spt/' . $sppd->id . '/' . md5($sppd->document_number . $sppd->id));
      $pdfData['qr_image'] = QrSimulator::generate($verifyUrl, 65);
    }

    $isDprdMember = $sppd->user->department?->getRootDepartment()?->type === DepartmentType::DPRD && $sppd->user->isDprdMember();

    $viewName = $isDprdMember ? 'exports.spt_dprd' : 'exports.spt';

    return Pdf::loadView($viewName, compact('sppd', 'pdfData'))
      ->setPaper('f4', 'portrait')
      ->stream('SPT-' . Str::slug($sppd->document_number ?: $sppd->id) . '.pdf');
  }

  public function streamSppd(SppdRequest $sppd, ?User $user = null)
  {
    $this->authorizeSppdAccess($sppd);
    // Jika user_id dikirim lewat request (untuk pengikut)
    if (request()->has('user_id')) {
      $decoded = Hashids::decode(request()->user_id);
      $userId = ! empty($decoded) ? $decoded[0] : request()->user_id;
      $targetUser = User::findOrFail($userId);
    } else {
      $targetUser = ($user && $user->id) ? $user : $sppd->user;
    }

    // Check if already TTE signed for this target user, if so stream the signed file
    $signatureType = 'sppd_' . $targetUser->id;
    $signature = $sppd->digitalSignatures()
      ->where('document_type', $signatureType)
      ->where('status', SignatureStatus::SIGNED)
      ->first();

    if ($signature && $signature->signed_file_path) {
      $filePath = storage_path('app/public/' . $signature->signed_file_path);
      if (file_exists($filePath)) {
        return response()->file($filePath, [
          'Content-Type' => 'application/pdf',
          'Content-Disposition' => 'inline; filename="SPPD-' . Str::slug($targetUser->name) . '.pdf"',
        ]);
      }
    }

    $sppd->load(['user.department', 'user.rank', 'budget', 'category', 'destinations.regency', 'followers.user.rank']);
    $duration = Carbon::parse($sppd->start_date)->diffInDays(Carbon::parse($sppd->end_date)) + 1;

    $isMain = $targetUser->id === $sppd->user_id;

    $isDprd = $targetUser->department?->type === DepartmentType::DPRD
      || $targetUser->department?->parent?->type === DepartmentType::DPRD;

    $sppdApproval = $this->resolveSppdApproval($sppd, $targetUser);

    $approver = $sppdApproval?->approver;
    $approverRole = $approver?->position_name
      ?? $approver?->position?->name
      ?? $sppdApproval?->role_label
      ?? 'Kepala Dinas';

    $follower = $sppd->followers->where('user_id', $targetUser->id)->first();
    $pdfData = [
      'approver_name' => $approver?->name ?? '................................',
      'approver_role' => $approverRole,
      'approver_nip' => $approver?->nip ?? null,
      'approver_rank' => $approver?->rank?->name ?? '',
      'approver_group' => $approver?->rank?->group ?? '',
      'is_walikota' => false,
      'is_approved' => in_array($sppd->status, [SppdStatus::APPROVED, SppdStatus::COMPLETED]),
      'qr_image' => null,
      'duration' => $duration,
      'is_follower' => ! $isMain,
      'travel_position' => $follower?->travel_position,
      'letterhead_url' => $targetUser->department?->getInheritedLetterhead(),
    ];

    if ($pdfData['is_approved'] && $sppd->isSigned('sppd')) {
      $verifyUrl = url('/verify/sppd/' . $sppd->id . '/' . md5($sppd->document_number . $targetUser->id));
      $pdfData['qr_image'] = QrSimulator::generate($verifyUrl, 50);
    }

    return Pdf::loadView('exports.sppd', [
      'sppd' => $sppd,
      'user' => $targetUser,
      'is_main' => $isMain,
      'pdfData' => $pdfData,
    ])->setPaper([0, 0, 935.43, 684.45])
      ->stream('SPPD-' . Str::slug($targetUser->name) . '.pdf');
  }

  public function destroy(SppdRequest $sppd)
  {
    $isAdmin = Auth::user()->hasAnyRole(['admin_opd', 'super_admin']);
    if (! $isAdmin) {
      return back()->with('error', 'Anda tidak memiliki akses untuk menghapus SPPD ini.');
    }

    if ($sppd->status !== SppdStatus::IN_PROGRESS) {
      return back()->with('error', 'SPPD yang sudah disetujui penuh atau selesai tidak dapat dihapus.');
    }

    $sppd->delete();

    return redirect()->route('sppd.index')->with('success', 'Pengajuan SPPD berhasil dibatalkan dan dihapus.');
  }

  /**
   * Stream PDF: Kuitansi Rampung
   */
  public function streamKuitansiRampung(SppdRequest $sppd, Request $request)
  {
    $this->authorizeSppdAccess($sppd);
    $sppd->load([
      'user.department.head',
      'user.department.parent',
      'user.rank',
      'user.position',
      'budget.department',
      'followers.user.rank',
      'advanceReceipts',
      'actualExpenses',
      'costDetails',
      'approvals.approver',
    ]);

    $userId = $request->query('user_id', $sppd->user_id);
    $targetUser = User::with(['rank', 'position'])->findOrFail($userId);

    // Hitung biaya
    $panjar = $sppd->advanceReceipts->where('user_id', $userId)->first();
    $panjarAmount = $panjar?->amount ?? 0;
    $totalCosts = $sppd->costDetails->where('user_id', $userId)->sum('total');       // Rincian Biaya
    $totalActual = $sppd->actualExpenses->where('user_id', $userId)->sum('amount');   // Pengeluaran Riil

    // Uang Sebesar = Total Rincian Biaya + Total Pengeluaran Riil
    $uangSebesar = $totalCosts + $totalActual;

    // Data instansi & budget
    $department = $sppd->user->department;
    $budget = $sppd->budget;
    $deptName = $department?->name ?? '-';

    // Pimpinan OPD = Pengguna Anggaran
    // Cari user role kepala_opd di department user (atau parent OPD jika di sub-unit)
    $opdDept = $department;
    if ($opdDept && $opdDept->parent_id) {
      // Jika user berada di sub-unit, naik ke OPD induk
      $opdDept = $opdDept->parent ?? $opdDept;
    }

    $pimpinan = User::role('kepala_opd')
      ->where('department_id', $opdDept?->id)
      ->where('is_active', true)
      ->first();

    // Fallback: cari di department user langsung jika tidak ditemukan di induk
    if (! $pimpinan && $opdDept?->id !== $department?->id) {
      $pimpinan = User::role('kepala_opd')
        ->where('department_id', $department?->id)
        ->where('is_active', true)
        ->first();
    }

    // Bendahara pengeluaran berdasarkan jabatan
    $bendahara = User::whereHas('position', fn($q) => $q->where('name', 'Bendahara Pengeluaran'))
      ->where('department_id', $department?->id)
      ->where('is_active', true)
      ->first();

    // Fallback ke department induk
    if (! $bendahara && $department?->parent_id) {
      $bendahara = User::whereHas('position', fn($q) => $q->where('name', 'Bendahara Pengeluaran'))
        ->where('department_id', $department->parent_id)
        ->where('is_active', true)
        ->first();
    }

    // Custom date if passed
    $dateParam = $request->query('date');
    $dateValue = now();
    if ($dateParam) {
      try {
        $dateValue = Carbon::parse($dateParam);
      } catch (\Exception $e) {
        Log::warning('Parameter tanggal tidak valid pada stream PDF, memakai tanggal hari ini.', ['date' => $dateParam]);
      }
    }

    // QR Code
    $pdfUrl = route('sppd.stream.kuitansi-rampung', ['sppd' => $sppd->id, 'user_id' => $userId]);
    if ($dateParam) {
      $pdfUrl .= '&date=' . urlencode($dateParam);
    }
    $qrImage = QrSimulator::generate($pdfUrl);

    $pdfData = [
      // Data anggaran
      'tahun_anggaran' => $budget?->year ?? date('Y'),
      'kode_rekening' => $budget?->account_code ?? '-',
      'dept_name' => $deptName,

      // Data keuangan
      'uang_sebesar' => $uangSebesar,
      'terbilang_uang' => Terbilang::rupiah($uangSebesar),

      // Pengguna Anggaran = Pimpinan OPD (role kepala_opd)
      'approver_name' => $pimpinan?->name ?? '................................',
      'approver_nip' => $pimpinan?->nip,
      'approver_dept' => $opdDept?->name ?? $deptName,

      // Bendahara Pengeluaran
      'bendahara_name' => $bendahara?->name ?? '................................',
      'bendahara_nip' => $bendahara?->nip,

      // QR & Misc
      'qr_image' => $qrImage,
      'bku' => null,
      'date' => $dateValue->translatedFormat('d F Y'),
    ];

    return Pdf::loadView('exports.kuitansi_rampung', compact('sppd', 'targetUser', 'pdfData'))
      ->setPaper('f4', 'portrait')
      ->stream('KUITANSI_RAMPUNG-' . Str::slug($targetUser->name) . '.pdf');
  }

  /**
   * Stream PDF: Kuitansi Panjar
   */
  public function streamKuitansiPanjar(SppdRequest $sppd, Request $request)
  {
    $this->authorizeSppdAccess($sppd);
    $sppd->load([
      'user.department.head',
      'user.department.parent',
      'user.rank',
      'user.position',
      'budget.department',
      'followers.user.rank',
      'advanceReceipts',
      'approvals.approver',
    ]);

    $userId = $request->query('user_id', $sppd->user_id);
    $targetUser = User::with(['rank', 'position'])->findOrFail($userId);

    // Kuitansi Panjar
    $panjar = $sppd->advanceReceipts->where('user_id', $userId)->first();
    $panjarAmount = $panjar?->amount ?? 0;

    // Data instansi & budget
    $department = $sppd->user->department;
    $budget = $sppd->budget;
    $deptName = $department?->name ?? '-';

    // Pimpinan OPD = Pengguna Anggaran
    $opdDept = $department;
    if ($opdDept && $opdDept->parent_id) {
      $opdDept = $opdDept->parent ?? $opdDept;
    }

    $pimpinan = User::role('kepala_opd')
      ->where('department_id', $opdDept?->id)
      ->where('is_active', true)
      ->first();

    if (! $pimpinan && $opdDept?->id !== $department?->id) {
      $pimpinan = User::role('kepala_opd')
        ->where('department_id', $department?->id)
        ->where('is_active', true)
        ->first();
    }

    // Bendahara pengeluaran berdasarkan jabatan
    $bendahara = User::whereHas('position', fn($q) => $q->where('name', 'Bendahara Pengeluaran'))
      ->where('department_id', $department?->id)
      ->where('is_active', true)
      ->first();

    // Fallback ke department induk
    if (! $bendahara && $department?->parent_id) {
      $bendahara = User::whereHas('position', fn($q) => $q->where('name', 'Bendahara Pengeluaran'))
        ->where('department_id', $department->parent_id)
        ->where('is_active', true)
        ->first();
    }

    // Custom date if passed
    $dateParam = $request->query('date');
    $dateValue = now();
    if ($dateParam) {
      try {
        $dateValue = Carbon::parse($dateParam);
      } catch (\Exception $e) {
        Log::warning('Parameter tanggal tidak valid pada stream PDF, memakai tanggal hari ini.', ['date' => $dateParam]);
      }
    }

    // QR Code
    $pdfUrl = route('sppd.stream.kuitansi-panjar', ['sppd' => $sppd->id, 'user_id' => $userId]);
    if ($dateParam) {
      $pdfUrl .= '&date=' . urlencode($dateParam);
    }
    $qrImage = QrSimulator::generate($pdfUrl);

    $pdfData = [
      // Data anggaran
      'tahun_anggaran' => $budget?->year ?? date('Y'),
      'kode_rekening' => $budget?->account_code ?? '-',
      'dept_name' => $deptName,

      // Data keuangan
      'uang_sebesar' => $panjarAmount,
      'terbilang_uang' => Terbilang::rupiah($panjarAmount),

      // Pengguna Anggaran = Pimpinan OPD (role kepala_opd)
      'approver_name' => $pimpinan?->name ?? '................................',
      'approver_nip' => $pimpinan?->nip,
      'approver_dept' => $opdDept?->name ?? $deptName,

      // Bendahara Pengeluaran
      'bendahara_name' => $bendahara?->name ?? '................................',
      'bendahara_nip' => $bendahara?->nip,

      // QR & Misc
      'qr_image' => $qrImage,
      'bku' => null,
      'date' => $dateValue->translatedFormat('d F Y'),
    ];

    return Pdf::loadView('exports.kuitansi_rampung', compact('sppd', 'targetUser', 'pdfData'))
      ->setPaper('f4', 'portrait')
      ->stream('KUITANSI_PANJAR-' . Str::slug($targetUser->name) . '.pdf');
  }

  /**
   * Stream PDF: Laporan Pengeluaran Riil
   */
  public function streamPengeluaranRiil(SppdRequest $sppd, Request $request)
  {
    $this->authorizeSppdAccess($sppd);
    $sppd->load([
      'user.department.parent',
      'user.department.head',
      'user.rank',
      'user.position',
      'pptk.rank',
      'actualExpenses',
      'approvals.approver',
    ]);

    $userId = $request->query('user_id', $sppd->user_id);
    $targetUser = User::with(['rank', 'position', 'roles'])->findOrFail($userId);
    $expenses = $sppd->actualExpenses->where('user_id', $userId)->values();

    $pptk = $sppd->pptk;

    // Data instansi
    $department = $sppd->user->department;

    // Pimpinan OPD (Kepala Dinas)
    $opdDept = $department;
    if ($opdDept && $opdDept->parent_id) {
      $opdDept = $opdDept->parent ?? $opdDept;
    }

    $pimpinan = User::role('kepala_opd')
      ->where('department_id', $opdDept?->id)
      ->where('is_active', true)
      ->first();

    if (! $pimpinan && $opdDept?->id !== $department?->id) {
      $pimpinan = User::role('kepala_opd')
        ->where('department_id', $department?->id)
        ->where('is_active', true)
        ->first();
    }

    $lastApproval = $sppd->approvals()->reorder('step_order', 'desc')->first();
    $approver = $lastApproval?->approver;

    // Custom date if passed
    $dateParam = $request->query('date');
    $dateValue = now();
    if ($dateParam) {
      try {
        $dateValue = Carbon::parse($dateParam);
      } catch (\Exception $e) {
        Log::warning('Parameter tanggal tidak valid pada stream PDF, memakai tanggal hari ini.', ['date' => $dateParam]);
      }
    }

    // QR Code
    $pdfUrl = route('sppd.stream.pengeluaran-riil', ['sppd' => $sppd->id, 'user_id' => $userId]);
    if ($dateParam) {
      $pdfUrl .= '&date=' . urlencode($dateParam);
    }
    $qrImage = QrSimulator::generate($pdfUrl);

    $pdfData = [
      'pptk_name' => $pptk?->name ?? '_________________________',
      'pptk_nip' => $pptk?->nip,
      'pimpinan_name' => $pimpinan?->name ?? '_________________________',
      'pimpinan_nip' => $pimpinan?->nip,
      'pimpinan_role' => $pimpinan?->position?->name ?? 'Kepala Dinas',
      'is_walikota' => ($approver && $approver->hasRole('walikota')) || ($pimpinan && $pimpinan->hasRole('walikota')),
      'qr_image' => $qrImage,
      'date' => $dateValue->translatedFormat('d F Y'),
    ];

    return Pdf::loadView('exports.pengeluaran_riil', compact('sppd', 'targetUser', 'expenses', 'pdfData'))
      ->setPaper('f4', 'portrait')
      ->stream('PENGELUARAN_RILL-' . Str::slug($targetUser->name) . '.pdf');
  }

  /**
   * Stream PDF: Rincian Biaya Perjalanan Dinas
   */
  public function streamRincianBiaya(SppdRequest $sppd, Request $request)
  {
    $this->authorizeSppdAccess($sppd);
    $sppd->load([
      'user.department.parent',
      'user.rank',
      'user.position',
      'pptk.rank',
      'pptk.position',
      'costDetails',
      'actualExpenses',
      'advanceReceipts',
      'approvals.approver',
    ]);

    $userId = $request->query('user_id', $sppd->user_id);
    $targetUser = User::with(['rank', 'position', 'roles'])->findOrFail($userId);
    $costs = $sppd->costDetails->where('user_id', $userId)->values();

    // Total Pengeluaran Riil untuk baris pertama tabel
    $totalPengeluaranRiil = $sppd->actualExpenses->where('user_id', $userId)->sum('amount');

    // Kuitansi Panjar (yang telah dibayar semula)
    $panjarAmount = $sppd->advanceReceipts->where('user_id', $userId)->sum('amount');

    // Data instansi
    $department = $sppd->user->department;

    // Pimpinan OPD (Kepala Dinas)
    $opdDept = $department;
    if ($opdDept && $opdDept->parent_id) {
      $opdDept = $opdDept->parent ?? $opdDept;
    }

    $pimpinan = User::role('kepala_opd')
      ->where('department_id', $opdDept?->id)
      ->where('is_active', true)
      ->first();

    if (! $pimpinan && $opdDept?->id !== $department?->id) {
      $pimpinan = User::role('kepala_opd')
        ->where('department_id', $department?->id)
        ->where('is_active', true)
        ->first();
    }

    // Bendahara Pengeluaran berdasarkan jabatan
    $bendahara = User::whereHas('position', fn($q) => $q->where('name', 'Bendahara Pengeluaran'))
      ->where('department_id', $department?->id)
      ->where('is_active', true)
      ->first();

    // Fallback ke department induk
    if (! $bendahara && $department?->parent_id) {
      $bendahara = User::whereHas('position', fn($q) => $q->where('name', 'Bendahara Pengeluaran'))
        ->where('department_id', $department->parent_id)
        ->where('is_active', true)
        ->first();
    }

    // PPTK
    $pptk = $sppd->pptk;

    // Custom date if passed
    $dateParam = $request->query('date');
    $dateValue = now();
    if ($dateParam) {
      try {
        $dateValue = Carbon::parse($dateParam);
      } catch (\Exception $e) {
        Log::warning('Parameter tanggal tidak valid pada stream PDF, memakai tanggal hari ini.', ['date' => $dateParam]);
      }
    }

    // QR Code
    $pdfUrl = route('sppd.stream.rincian-biaya', ['sppd' => $sppd->id, 'user_id' => $userId]);
    if ($dateParam) {
      $pdfUrl .= '&date=' . urlencode($dateParam);
    }
    $qrImage = QrSimulator::generate($pdfUrl);

    $pdfData = [
      'pptk_name' => $pptk?->name ?? '_________________________',
      'pptk_nip' => $pptk?->nip,
      'pimpinan_name' => $pimpinan?->name ?? '_________________________',
      'pimpinan_nip' => $pimpinan?->nip,
      'pimpinan_role' => $pimpinan?->position?->name ?? 'Kepala Dinas',
      'bendahara_name' => $bendahara?->name ?? '_________________________',
      'bendahara_nip' => $bendahara?->nip,
      'panjar_amount' => $panjarAmount,
      'qr_image' => $qrImage,
      'date' => $dateValue->translatedFormat('d F Y'),
    ];

    return Pdf::loadView('exports.rincian_biaya', compact('sppd', 'targetUser', 'costs', 'totalPengeluaranRiil', 'pdfData'))
      ->setPaper('f4', 'portrait')
      ->stream('RBPD-' . Str::slug($targetUser->name) . '.pdf');
  }


}
