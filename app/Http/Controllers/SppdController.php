<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Helpers\QrSimulator;
use App\Models\Budget;
use App\Models\Province;
use App\Models\SppdApproval;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SppdController extends Controller
{
  public function index(Request $request)
  {
    $query = SppdRequest::with(['user', 'category', 'budget.department']);

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

  public function create()
  {
    $query = User::where('is_active', true);

    // Filter: Admin OPD hanya bisa melihat pegawai di instansinya sendiri
    if (!Auth::user()->hasRole('super_admin')) {
      $query->where('department_id', Auth::user()->department_id);
    }

    $users = $query->orderBy('name')->get();
    return view('sppd.create', compact('users'));
  }

  public function createDetails(Request $request)
  {
    $request->validate([
      'user_id' => 'required|exists:users,id',
      'domain'  => 'required|in:dalam_daerah,lddp,ldlp'
    ]);

    $pelaksana = User::with('department')->findOrFail($request->user_id);

    // Keamanan tambahan: Pastikan Admin OPD tidak menembak user_id dari OPD lain lewat URL
    if (!Auth::user()->hasRole('super_admin') && $pelaksana->department_id !== Auth::user()->department_id) {
      return redirect()->route('sppd.create')->with('error', 'Anda tidak memiliki akses untuk membuat SPPD bagi pegawai di luar instansi Anda.');
    }

    $domain = $request->domain;

    // Validasi apakah alur tersedia
    $workflowService = new SppdWorkflowService();
    $steps = $workflowService->simulateApprovals($pelaksana, $domain);

    if (empty($steps)) {
      return redirect()->route('sppd.create')
        ->with('error', 'Alur pengajuan untuk pelaksana ini belum diatur. Harap hubungi Admin.');
    }

    // Cek apakah pelaksana masih dalam perjalanan aktif
    // (status IN_PROGRESS atau APPROVED dengan end_date belum lewat)
    $hasActiveTravel = SppdRequest::where('user_id', $pelaksana->id)
      ->where(function ($q) {
        $q->where('status', SppdStatus::IN_PROGRESS)
          ->orWhere(function ($q2) {
            $q2->where('status', SppdStatus::APPROVED)
               ->where('end_date', '>=', today());
          });
      })
      ->exists();

    if ($hasActiveTravel) {
      return redirect()->route('sppd.create')
        ->with('error', 'Pegawai ' . $pelaksana->name . ' masih memiliki SPPD aktif (sedang dalam proses approval atau masih dalam periode perjalanan). SPPD baru dapat diajukan setelah tanggal perjalanan selesai.');
    }

    // Cek kelengkapan pejabat
    foreach ($steps as $step) {
      if ($step['status'] !== 'found') {
        return redirect()->route('sppd.create')
          ->with('error', 'Pejabat penanggung jawab (' . $step['role_label'] . ') belum diatur di unit kerja terkait.');
      }
    }

    // Filter Anggaran: Hanya yang milik instansi user login
    $budgetQuery = Budget::with('department');
    if (!Auth::user()->hasRole('super_admin')) {
      $budgetQuery->where('department_id', Auth::user()->department_id);
    }
    $budgets = $budgetQuery->get();

    $categories = SppdCategory::all();

    // Filter Pengikut: Hanya yang satu instansi
    $userQuery = User::where('is_active', true);
    if (!Auth::user()->hasRole('super_admin')) {
      $userQuery->where('department_id', Auth::user()->department_id);
    }
    $users = $userQuery->orderBy('name')->get();

    $provinces = Province::orderBy('name')->get();

    // Kumpulkan ID user yang sedang dalam perjalanan aktif (untuk disable di pilihan pengikut)
    $activeFollowerIds = SppdRequest::whereIn('user_id', $users->pluck('id'))
      ->where(function ($q) {
        $q->where('status', SppdStatus::IN_PROGRESS)
          ->orWhere(function ($q2) {
            $q2->where('status', SppdStatus::APPROVED)
               ->where('end_date', '>=', today());
          });
      })
      ->pluck('user_id')
      ->unique()
      ->toArray();

    return view('sppd.create_details', compact('pelaksana', 'domain', 'budgets', 'categories', 'users', 'provinces', 'steps', 'activeFollowerIds'));
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'user_id'         => 'required|exists:users,id',
      'budget_id'       => 'required|exists:budgets,id',
      'category_id'     => 'required|exists:sppd_categories,id',
      'purpose'         => 'required|string|max:1000',
      'problem'         => 'nullable|string',
      'facts'           => 'nullable|string',
      'analysis'        => 'nullable|string',
      'start_date'      => 'required|date',
      'end_date'        => 'required|date|after_or_equal:start_date',
      'transport_type'  => 'nullable|string|max:255',
      'transport_name'  => 'nullable|string|max:255',
      'departure_place' => 'nullable|string|max:255',
      'domain'          => 'required|in:dalam_daerah,lddp,ldlp',
      'urgency'         => 'nullable|string|max:255',
      'sppd_date'       => 'nullable|date',
      'spt_date'        => 'nullable|date',
      'notes'           => 'nullable|string|max:1000',
      // Destinations
      'destinations'            => 'required|array|min:1',
      'destinations.*.province_id' => 'required_if:domain,lddp,ldlp|exists:provinces,id',
      'destinations.*.regency_id'  => 'required_if:domain,lddp,ldlp|exists:regencies,id',
      'destinations.*.address'     => 'required_if:domain,lddp,ldlp|string|max:500',
      'destinations.*.address_only' => 'required_if:domain,dalam_daerah|string|max:500',
      // Followers
      'followers'     => 'nullable|array',
      'followers.*'   => 'exists:users,id',
      // Cost details
      'costs'                => 'nullable|array',
      'costs.*.description'  => 'required_with:costs|string|max:255',
      'costs.*.unit_cost'    => 'required_with:costs|numeric|min:0',
      'costs.*.quantity'     => 'required_with:costs|integer|min:1',
    ]);

    // Cek aktif perjalanan sebelum menyimpan (double-check server side)
    $hasActiveTravel = SppdRequest::where('user_id', $validated['user_id'])
      ->where(function ($q) {
        $q->where('status', SppdStatus::IN_PROGRESS)
          ->orWhere(function ($q2) {
            $q2->where('status', SppdStatus::APPROVED)
               ->where('end_date', '>=', today());
          });
      })
      ->exists();

    if ($hasActiveTravel) {
      return back()->withInput()->with('error', 'Pegawai ini masih memiliki SPPD aktif. SPPD baru hanya dapat diajukan setelah tanggal perjalanan sebelumnya selesai.');
    }

    try {
      DB::transaction(function () use ($validated, $request, &$sppd) {
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
          $attachmentPath = $request->file('attachment')->store('sppd/attachments', 'public');
        }

        $sppd = SppdRequest::create([
          'user_id'         => $validated['user_id'],
          'creator_id'      => Auth::id(),
          'budget_id'       => $validated['budget_id'],
          'category_id'     => $validated['category_id'],
          'purpose'         => $validated['purpose'],
          'problem'         => $validated['problem'] ?? null,
          'facts'           => $validated['facts'] ?? null,
          'analysis'        => $validated['analysis'] ?? null,
          'start_date'      => $validated['start_date'],
          'end_date'        => $validated['end_date'],
          'transport_type'  => $validated['transport_type'],
          'transport_name'  => $validated['transport_name'],
          'departure_place' => $validated['departure_place'],
          'domain'          => $validated['domain'],
          'urgency'         => $validated['urgency'],
          'sppd_date'       => $validated['sppd_date'],
          'spt_date'        => $validated['spt_date'],
          'status'          => SppdStatus::IN_PROGRESS,
          'notes'           => $validated['notes'] ?? null,
          'attachment'      => $attachmentPath,
        ]);

        // Save destinations
        foreach ($validated['destinations'] as $dest) {
          if ($sppd->domain->value === 'dalam_daerah') {
            $sultra = \App\Models\Province::where('name', 'Sulawesi Tenggara')->first();
            $kendari = \App\Models\Regency::where('name', 'LIKE', '%Kendari%')->where('province_id', $sultra?->id)->first();

            $sppd->destinations()->create([
              'address' => $dest['address_only'],
              'province_id' => $sultra?->id,
              'regency_id' => $kendari?->id,
            ]);
          } else {
            $sppd->destinations()->create([
              'province_id' => $dest['province_id'] ?? null,
              'regency_id' => $dest['regency_id'] ?? null,
              'address' => $dest['address'] ?? null,
            ]);
          }
        }

        // Save followers
        if (!empty($validated['followers'])) {
          foreach ($validated['followers'] as $userId) {
            $sppd->followers()->create(['user_id' => $userId]);
          }
        }

        // Generate approvals via dynamic workflow
        $workflowService = app(\App\Services\SppdWorkflowService::class);
        $success = $workflowService->generateApprovals($sppd);

        if (!$success) {
          throw new \Exception('Sistem belum memiliki aturan Workflow untuk instansi, peran pemohon, atau tujuan tersebut. Silakan hubungi admin untuk melengkapi aturan Workflow SPPD.');
        }

        // PDF generation is now handled on-the-fly via streaming to save storage.
        // Files will only be saved permanently when the document is officially signed.
      });

      return redirect()->route('sppd.show', $sppd->id)
        ->with('success', 'SPPD berhasil dibuat dan diajukan. Gunakan tombol "Portal Selanjutnya" untuk mengelola dokumen.');
    } catch (\Exception $e) {
      return back()->withInput()->with('error', $e->getMessage());
    }
  }

  public function show(SppdRequest $sppd)
  {
    $sppd->load([
      'user.department',
      'creator',
      'pptk',
      'budget.department',
      'category',
      'destinations.province',
      'destinations.regency',
      'followers.user',
      'approvals.approver',
      'costDetails',
      'actualExpenses',
      'advanceReceipts',
      'report',
      'digitalSignatures.signer',
    ]);

    return view('sppd.show', compact('sppd'));
  }

  /**
   * Portal 'Selanjutnya' (Image 2)
   */
  public function next(SppdRequest $sppd)
  {
    $sppd->load(['user', 'followers.user']);
    return view('sppd.next', compact('sppd'));
  }

  /**
   * Halaman Kelola SPPD (Image 1)
   */
  public function manageSppd(SppdRequest $sppd)
  {
    $sppd->load(['user', 'followers.user']);
    return view('sppd.manage_sppd', compact('sppd'));
  }

  /**
   * Halaman Kelola SPT (Image 2)
   */
  public function manageSpt(SppdRequest $sppd)
  {
    $sppd->load(['user']);
    return view('sppd.manage_spt', compact('sppd'));
  }

  /**
   * Reset TTE (Electronic Signature) for a specific document type
   */
  public function resetTte(SppdRequest $sppd, $type)
  {
    // Logic to clear existing digital signatures for this document type
    $sppd->digitalSignatures()->where('document_type', $type)->delete();

    return back()->with('success', "TTE untuk " . strtoupper($type) . " berhasil di-reset.");
  }

  /**
   * Halaman Kuitansi (Image 3)
   */
  public function receipts(SppdRequest $sppd)
  {
    $sppd->load(['user', 'advanceReceipts']);
    return view('sppd.costs.receipts', compact('sppd'));
  }

  /**
   * Halaman Laporan Pengeluaran Rill (Image 4)
   */
  public function actualExpenses(SppdRequest $sppd)
  {
    $sppd->load(['user', 'actualExpenses']);
    return view('sppd.costs.actuals', compact('sppd'));
  }

  /**
   * Halaman Rincian Biaya Perjalanan Dinas (Image 5)
   */
  public function finalCosts(SppdRequest $sppd)
  {
    $sppd->load(['user', 'costDetails']);
    return view('sppd.costs.final_details', compact('sppd'));
  }

  /**
   * Halaman Input Laporan Perjalanan
   */
  public function reportInput(SppdRequest $sppd)
  {
    $sppd->load(['user', 'report']);
    return view('sppd.report_input', compact('sppd'));
  }





  public function approve(Request $request, SppdRequest $sppd)
  {
    $approval = $sppd->approvals()
      ->where('approver_id', Auth::id())
      ->where('status', ApprovalStatus::PENDING)
      ->first();

    if (!$approval) {
      return back()->with('error', 'Anda tidak memiliki hak untuk menyetujui SPPD ini.');
    }

    $approval->approve($request->notes);

    // Check if all approvals are approved
    $allApproved = $sppd->approvals()->where('status', '!=', ApprovalStatus::APPROVED)->doesntExist();
    if ($allApproved) {
      $sppd->update(['status' => SppdStatus::APPROVED]);
    }

    return back()->with('success', 'SPPD berhasil disetujui.');
  }

  public function reject(Request $request, SppdRequest $sppd)
  {
    $request->validate(['notes' => 'required|string|max:500']);

    $approval = $sppd->approvals()
      ->where('approver_id', Auth::id())
      ->where('status', ApprovalStatus::PENDING)
      ->first();

    if (!$approval) {
      return back()->with('error', 'Anda tidak memiliki hak untuk menolak SPPD ini.');
    }

    $approval->reject($request->notes);
    $sppd->update(['status' => SppdStatus::REJECTED]);

    return back()->with('success', 'SPPD ditolak.');
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
      'domain' => 'nullable|string'
    ]);

    $user = User::with('department.parent')->find($request->user_id);
    $workflowService = app(\App\Services\SppdWorkflowService::class);

    $steps = $workflowService->simulateApprovals($user, $request->domain);

    return response()->json([
      'user' => [
        'name' => $user->name,
        'department' => $user->department?->name ?? 'Tanpa Unit Kerja',
        'role' => $user->getRoleNames()->first() ?? 'Tanpa Role',
      ],
      'has_header' => (bool) ($user->department?->letterhead && \Illuminate\Support\Str::contains($user->department->letterhead, '/')),
      'steps' => $steps
    ]);
  }
  public function streamSpt(SppdRequest $sppd)
  {
    $sppd->load(['user.department', 'user.rank', 'budget', 'category', 'destinations.regency', 'followers.user.rank']);
    $lastApproval = $sppd->approvals()->reorder('step_order', 'desc')->first();
    $duration = \Carbon\Carbon::parse($sppd->start_date)->diffInDays(\Carbon\Carbon::parse($sppd->end_date)) + 1;

    $approver = $lastApproval->approver;
    $approverRole = $approver->position_name
      ?? $approver->position?->name
      ?? $lastApproval->role_label
      ?? 'Kepala Dinas';

    $pdfData = [
      'approver_name'  => $approver->name ?? '................................',
      'approver_role'  => $approverRole,
      'approver_nip'   => $approver->nip ?? null,
      'approver_rank'  => $approver->rank->name ?? '',
      'approver_group' => $approver->rank->group ?? '',
      'is_walikota'    => $approver && $approver->hasRole('walikota'),
      'is_approved'    => in_array($sppd->status, [SppdStatus::APPROVED, SppdStatus::COMPLETED]),
      'qr_image'       => null,
      'duration'       => $duration
    ];

    // Generate QR code simulasi jika sudah disetujui
    if ($pdfData['is_approved']) {
      $verifyUrl = url('/verify/spt/' . $sppd->id . '/' . md5($sppd->document_number . $sppd->id));
      $pdfData['qr_image'] = QrSimulator::generate($verifyUrl, 150);
    }

    return \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.spt', compact('sppd', 'pdfData'))
      ->setPaper('f4', 'portrait')
      ->stream('SPT-' . \Illuminate\Support\Str::slug($sppd->document_number ?: $sppd->id) . '.pdf');
  }

  public function streamSppd(SppdRequest $sppd, User $user = null)
  {
    $sppd->load(['user.department', 'user.rank', 'budget', 'category', 'destinations.regency', 'followers.user.rank']);
    $duration = \Carbon\Carbon::parse($sppd->start_date)->diffInDays(\Carbon\Carbon::parse($sppd->end_date)) + 1;

    // Jika user_id dikirim lewat request (untuk pengikut)
    if (request()->has('user_id')) {
      $targetUser = User::findOrFail(request()->user_id);
    } else {
      $targetUser = ($user && $user->id) ? $user : $sppd->user;
    }

    $isMain = $targetUser->id === $sppd->user_id;

    // Untuk SPPD: penandatangan adalah pimpinan OPD (bukan Walikota/Sekda/Kepala Daerah).
    // Ambil approval step terakhir yang BUKAN dari role kepala daerah/sekda/walikota.
    $cityWideRoles = ['walikota', 'sekda', 'kepala_daerah'];

    $opdHeadApproval = $sppd->approvals()
      ->with('approver.roles')
      ->reorder('step_order', 'desc')
      ->get()
      ->first(function ($approval) use ($cityWideRoles) {
        return $approval->approver &&
          !$approval->approver->roles->pluck('name')->intersect($cityWideRoles)->isNotEmpty();
      });

    // Fallback ke step pertama jika semua approver adalah pejabat kota
    $sppdApproval = $opdHeadApproval ?? $sppd->approvals()->reorder('step_order', 'asc')->first();

    $approver = $sppdApproval?->approver;
    $approverRole = $approver?->position_name
      ?? $approver?->position?->name
      ?? $sppdApproval?->role_label
      ?? 'Kepala Dinas';

    $pdfData = [
      'approver_name' => $approver?->name ?? '................................',
      'approver_role' => $approverRole,
      'approver_nip'  => $approver?->nip ?? null,
      'approver_rank' => $approver?->rank?->name ?? '',
      'approver_group' => $approver?->rank?->group ?? '',
      'is_walikota'   => false,
      'is_approved'   => in_array($sppd->status, [SppdStatus::APPROVED, SppdStatus::COMPLETED]),
      'qr_image'      => null,
      'duration'      => $duration
    ];

    // Generate QR code simulasi jika sudah disetujui
    if ($pdfData['is_approved']) {
      $verifyUrl = url('/verify/sppd/' . $sppd->id . '/' . md5($sppd->document_number . $targetUser->id));
      $pdfData['qr_image'] = QrSimulator::generate($verifyUrl, 150);
    }

    return \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.sppd', [
      'sppd' => $sppd,
      'user' => $targetUser,
      'is_main' => $isMain,
      'pdfData' => $pdfData
    ])->setPaper([0, 0, 935.43, 684.45])
      ->stream('SPPD-' . \Illuminate\Support\Str::slug($targetUser->name) . '.pdf');
  }

  public function destroy(SppdRequest $sppd)
  {
    $isOwner = Auth::id() === $sppd->creator_id || Auth::id() === $sppd->user_id;
    if (!$isOwner) {
      return back()->with('error', 'Anda tidak memiliki akses untuk menghapus SPPD ini.');
    }

    if ($sppd->status !== SppdStatus::DRAFT && $sppd->status !== SppdStatus::IN_PROGRESS) {
      return back()->with('error', 'SPPD yang sudah disetujui penuh atau selesai tidak dapat dihapus.');
    }

    $sppd->delete();

    return redirect()->route('sppd.index')->with('success', 'Pengajuan SPPD berhasil dibatalkan dan dihapus.');
  }
}
