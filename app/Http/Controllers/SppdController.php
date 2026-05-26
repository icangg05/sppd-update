<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\SignatureDocumentType;
use App\Enums\SignatureStatus;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Helpers\QrSimulator;
use App\Models\Budget;
use App\Models\Province;
use App\Models\SppdApproval;
use App\Models\SppdCategory;
use App\Models\SppdDigitalSignature;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\SppdWorkflowService;
use App\Services\Tte\SignatureService;
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
        ->with('error', 'Pegawai ' . $pelaksana->name . ' masih memiliki SPPD aktif (sedang dalam proses approval atau masih dalam periode perjalanan).');
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
        ->with('success', 'SPPD berhasil dibuat dan diajukan. Silakan menunggu proses persetujuan.');
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
    $signatures = $sppd->digitalSignatures()
      ->where(function ($q) use ($type) {
        if ($type === 'sppd') {
          $q->where('document_type', 'sppd')
            ->orWhere('document_type', 'like', 'sppd_%');
        } else {
          $q->where('document_type', $type);
        }
      })
      ->get();

    foreach ($signatures as $sig) {
      if ($sig->signed_file_path) {
        \Illuminate\Support\Facades\Storage::disk(config('tte.storage.disk'))->delete($sig->signed_file_path);
      }
      $sig->delete();
    }

    // Set final approval step to pending
    $lastApproval = $sppd->approvals()->reorder('step_order', 'desc')->first();
    if ($lastApproval) {
      $lastApproval->update([
        'status' => \App\Enums\ApprovalStatus::PENDING,
        'acted_at' => null,
        'notes' => null,
      ]);
    }

    // Set SPPD status to IN_PROGRESS so it can be approved again
    $sppd->update(['status' => SppdStatus::IN_PROGRESS]);

    return back()->with('success', "TTE untuk " . strtoupper($type) . " berhasil di-reset dan status dikembalikan untuk disetujui ulang oleh pejabat terakhir.");
  }

  /**
   * Halaman Kuitansi (Image 3)
   */
  public function receipts(SppdRequest $sppd)
  {
    $sppd->load(['user.department', 'budget.department', 'followers.user', 'advanceReceipts', 'actualExpenses', 'costDetails']);

    // Cari bendahara pengeluaran di OPD terkait
    $bendahara = User::role('bendahara')
      ->where('department_id', $sppd->user->department_id)
      ->where('is_active', true)
      ->first();

    return view('sppd.costs.receipts', compact('sppd', 'bendahara'));
  }

  /**
   * Halaman Laporan Pengeluaran Rill (Image 4)
   */
  public function actualExpenses(SppdRequest $sppd)
  {
    $sppd->load(['user', 'pptk', 'followers.user', 'actualExpenses.user']);

    // Kandidat PPTK: pegawai di OPD yang sama (termasuk sub-unit)
    $dept = $sppd->user->department;
    $opdId = $dept->parent_id ?? $dept->id; // naik ke OPD induk jika di sub-unit

    // Kumpulkan ID OPD induk + semua sub-unit
    $deptIds = \App\Models\Department::where('id', $opdId)
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
    $request->validate([
      'pptk_id' => 'required|exists:users,id',
    ]);

    $sppd->update(['pptk_id' => $request->pptk_id]);

    return back()->with('success', 'PPTK berhasil diperbarui.');
  }

  /**
   * Halaman Rincian Biaya Perjalanan Dinas (Image 5)
   */
  public function finalCosts(SppdRequest $sppd)
  {
    $sppd->load(['user.department', 'pptk', 'followers.user', 'costDetails.user']);

    // Cari bendahara pengeluaran di OPD terkait
    $bendahara = User::role('bendahara')
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
    $sppd->load(['user', 'report']);
    return view('sppd.report_input', compact('sppd'));
  }

  /**
   * Store or update Laporan Perjalanan
   */
  public function storeReport(Request $request, SppdRequest $sppd)
  {
    $validated = $request->validate([
      'report_text'        => 'required|string',
      'report_date'        => 'nullable|date',
      'report_file'        => 'nullable|file|max:20480',
      'documentation_file' => 'nullable|image|max:20480',
    ]);

    if ($request->hasFile('report_file')) {
      $validated['report_file'] = $request->file('report_file')
        ->store('sppd/reports', 'public');
    }

    if ($request->hasFile('documentation_file')) {
      $validated['documentation_file'] = $request->file('documentation_file')
        ->store('sppd/documentation', 'public');
    }

    // Calculate total expense from actual expenses
    $validated['total_expense'] = $sppd->actualExpenses()->sum('amount');

    $sppd->report()->updateOrCreate(
      ['sppd_request_id' => $sppd->id],
      $validated
    );

    return back()->with('success', 'Laporan perjalanan berhasil disimpan.');
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

    // Check if previous steps are all approved
    $hasUnapprovedPreviousSteps = $sppd->approvals()
      ->where('step_order', '<', $approval->step_order)
      ->where('status', '!=', ApprovalStatus::APPROVED->value)
      ->exists();

    if ($hasUnapprovedPreviousSteps) {
      return back()->with('error', 'Anda belum dapat menyetujui dokumen ini karena langkah persetujuan sebelumnya belum selesai.');
    }

    $isFinalApproval = $this->isFinalApprovalStep($sppd, $approval);

    if ($isFinalApproval && !Auth::user()?->nik) {
      return back()->with('error', 'NIK penandatangan belum tersedia. Silakan lengkapi profil Anda terlebih dahulu.');
    }

    $rules = ['notes' => 'nullable|string|max:500'];
    if ($isFinalApproval) {
      $rules['passphrase'] = 'required|string|min:4|max:255';
    }

    $validated = $request->validate($rules);

    if ($isFinalApproval) {
      $documentsToSign = [];

      // 1. SPT (type 'spt')
      $documentsToSign[] = [
        'type' => 'spt',
        'coords' => $this->defaultSignatureCoordinates(SignatureDocumentType::SPT)
      ];

      // 2. SPPD Pelaksana (type 'sppd_{user_id}')
      $documentsToSign[] = [
        'type' => 'sppd_' . $sppd->user_id,
        'coords' => $this->defaultSignatureCoordinates(SignatureDocumentType::SPPD)
      ];

      // 3. SPPD Followers (type 'sppd_{user_id}')
      foreach ($sppd->followers as $follower) {
        $documentsToSign[] = [
          'type' => 'sppd_' . $follower->user_id,
          'coords' => $this->defaultSignatureCoordinates(SignatureDocumentType::SPPD)
        ];
      }

      $signatureService = app(SignatureService::class);

      foreach ($documentsToSign as $doc) {
        $signature = $sppd->digitalSignatures()->updateOrCreate(
          [
            'signer_id' => Auth::id(),
            'document_type' => $doc['type'],
          ],
          array_merge(
            [
              'sppd_request_id' => $sppd->id,
              'signer_id' => Auth::id(),
              'document_type' => $doc['type'],
              'status' => SignatureStatus::PENDING,
              'error_message' => null,
              'signed_file_path' => null,
              'provider_name' => config('tte.default_provider'),
            ],
            $doc['coords']
          )
        );

        $signResult = $signatureService->sign($signature, $validated['passphrase']);

        if ($signResult !== true) {
          return back()
            ->with('error', 'Error TTE (' . strtoupper(str_replace('_', ' ', $doc['type'])) . '): ' . ($signature->error_message ?? 'Terjadi kesalahan saat memproses tanda tangan elektronik.'))
            ->with('error_details', is_array($signResult) && array_key_exists('details', $signResult) ? $signResult['details'] : $signature->toArray());
        }
      }

      $approval->approve($validated['notes'] ?? null);
      $allApproved = $sppd->approvals()->where('status', '!=', ApprovalStatus::APPROVED)->doesntExist();
      if ($allApproved) {
        $sppd->update(['status' => SppdStatus::APPROVED]);
      }

      return back()->with('success', 'SPPD berhasil disetujui dan seluruh dokumen (SPT & SPPD) telah di-TTE dengan sukses.');
    }

    $approval->approve($validated['notes'] ?? null);

    $allApproved = $sppd->approvals()->where('status', '!=', ApprovalStatus::APPROVED)->doesntExist();
    if ($allApproved) {
      $sppd->update(['status' => SppdStatus::APPROVED]);
    }

    return back()->with('success', 'SPPD berhasil disetujui.');
  }

  private function isFinalApprovalStep(SppdRequest $sppd, SppdApproval $approval): bool
  {
    return $approval->step_order === $sppd->approvals()->max('step_order');
  }

  private function defaultSignatureCoordinates(SignatureDocumentType $documentType): array
  {
    return match ($documentType) {
      SignatureDocumentType::SPPD => [
        'sign_page' => 1,
        'sign_x' => 220,
        'sign_y' => 100,
        'sign_width' => 545,
        'sign_height' => 130,
      ],
      SignatureDocumentType::SPT => [
        'sign_page' => 1,
        'sign_x' => 220,
        'sign_y' => 100,
        'sign_width' => 545,
        'sign_height' => 130,
      ],
      SignatureDocumentType::KUITANSI => [
        'sign_page' => 1,
        'sign_x' => 220,
        'sign_y' => 100,
        'sign_width' => 545,
        'sign_height' => 130,
      ],
    };
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
    // Check if already TTE signed, if so stream the signed file
    $signature = $sppd->signatureFor('spt');
    if ($signature && $signature->status->value === SignatureStatus::SIGNED->value && $signature->signed_file_path) {
      $filePath = storage_path('app/public/' . $signature->signed_file_path);
      if (file_exists($filePath)) {
        return response()->file($filePath, [
          'Content-Type' => 'application/pdf',
          'Content-Disposition' => 'inline; filename="SPT-' . Str::slug($sppd->document_number ?: $sppd->id) . '.pdf"'
        ]);
      }
    }

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

    if ($pdfData['is_approved'] && $sppd->isSigned('spt')) {
      $verifyUrl = url('/verify/spt/' . $sppd->id . '/' . md5($sppd->document_number . $sppd->id));
      $pdfData['qr_image'] = QrSimulator::generate($verifyUrl, 65);
    }

    return \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.spt', compact('sppd', 'pdfData'))
      ->setPaper('f4', 'portrait')
      ->stream('SPT-' . \Illuminate\Support\Str::slug($sppd->document_number ?: $sppd->id) . '.pdf');
  }

  public function streamSppd(SppdRequest $sppd, ?User $user = null)
  {
    // Jika user_id dikirim lewat request (untuk pengikut)
    if (request()->has('user_id')) {
      $targetUser = User::findOrFail(request()->user_id);
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
          'Content-Disposition' => 'inline; filename="SPPD-' . Str::slug($targetUser->name) . '.pdf"'
        ]);
      }
    }

    $sppd->load(['user.department', 'user.rank', 'budget', 'category', 'destinations.regency', 'followers.user.rank']);
    $duration = \Carbon\Carbon::parse($sppd->start_date)->diffInDays(\Carbon\Carbon::parse($sppd->end_date)) + 1;

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

    if ($pdfData['is_approved'] && $sppd->isSigned('sppd')) {
      $verifyUrl = url('/verify/sppd/' . $sppd->id . '/' . md5($sppd->document_number . $targetUser->id));
      $pdfData['qr_image'] = QrSimulator::generate($verifyUrl, 50);
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

  /**
   * Stream PDF: Kuitansi Rampung
   */
  public function streamKuitansiRampung(SppdRequest $sppd, Request $request)
  {
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
    $panjar       = $sppd->advanceReceipts->where('user_id', $userId)->first();
    $panjarAmount = $panjar?->amount ?? 0;
    $totalCosts   = $sppd->costDetails->where('user_id', $userId)->sum('total');       // Rincian Biaya
    $totalActual  = $sppd->actualExpenses->where('user_id', $userId)->sum('amount');   // Pengeluaran Riil

    // Uang Sebesar = Total Rincian Biaya + Total Pengeluaran Riil
    $uangSebesar = $totalCosts + $totalActual;

    // Data instansi & budget
    $department = $sppd->user->department;
    $budget     = $sppd->budget;
    $deptName   = $department?->name ?? '-';

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
    if (!$pimpinan && $opdDept?->id !== $department?->id) {
      $pimpinan = User::role('kepala_opd')
        ->where('department_id', $department?->id)
        ->where('is_active', true)
        ->first();
    }

    // Bendahara pengeluaran
    $bendahara = User::role('bendahara')
      ->where('department_id', $department?->id)
      ->where('is_active', true)
      ->first();

    // QR Code
    $pdfUrl  = route('sppd.stream.kuitansi-rampung', ['sppd' => $sppd->id, 'user_id' => $userId]);
    $qrImage = QrSimulator::generate($pdfUrl);

    $pdfData = [
      // Data anggaran
      'tahun_anggaran'    => $budget?->year ?? date('Y'),
      'kode_rekening'     => $budget?->account_code ?? '-',
      'dept_name'         => $deptName,

      // Data keuangan
      'uang_sebesar'      => $uangSebesar,
      'terbilang_uang'    => \App\Helpers\Terbilang::rupiah($uangSebesar),

      // Pengguna Anggaran = Pimpinan OPD (role kepala_opd)
      'approver_name'     => $pimpinan?->name ?? '................................',
      'approver_nip'      => $pimpinan?->nip,
      'approver_dept'     => $opdDept?->name ?? $deptName,

      // Bendahara Pengeluaran
      'bendahara_name'    => $bendahara?->name ?? '................................',
      'bendahara_nip'     => $bendahara?->nip,

      // QR & Misc
      'qr_image'          => $qrImage,
      'bku'               => null,
      'date'              => now()->translatedFormat('d F Y'),
    ];

    return \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.kuitansi_rampung', compact('sppd', 'targetUser', 'pdfData'))
      ->setPaper('f4', 'portrait')
      ->stream('KUITANSI_RAMPUNG-' . Str::slug($targetUser->name) . '.pdf');
  }

  /**
   * Stream PDF: Kuitansi Panjar
   */
  public function streamKuitansiPanjar(SppdRequest $sppd, Request $request)
  {
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
    $panjar       = $sppd->advanceReceipts->where('user_id', $userId)->first();
    $panjarAmount = $panjar?->amount ?? 0;

    // Data instansi & budget
    $department = $sppd->user->department;
    $budget     = $sppd->budget;
    $deptName   = $department?->name ?? '-';

    // Pimpinan OPD = Pengguna Anggaran
    $opdDept = $department;
    if ($opdDept && $opdDept->parent_id) {
      $opdDept = $opdDept->parent ?? $opdDept;
    }

    $pimpinan = User::role('kepala_opd')
      ->where('department_id', $opdDept?->id)
      ->where('is_active', true)
      ->first();

    if (!$pimpinan && $opdDept?->id !== $department?->id) {
      $pimpinan = User::role('kepala_opd')
        ->where('department_id', $department?->id)
        ->where('is_active', true)
        ->first();
    }

    // Bendahara pengeluaran
    $bendahara = User::role('bendahara')
      ->where('department_id', $department?->id)
      ->where('is_active', true)
      ->first();

    // QR Code
    $pdfUrl  = route('sppd.stream.kuitansi-panjar', ['sppd' => $sppd->id, 'user_id' => $userId]);
    $qrImage = QrSimulator::generate($pdfUrl);

    $pdfData = [
      // Data anggaran
      'tahun_anggaran'    => $budget?->year ?? date('Y'),
      'kode_rekening'     => $budget?->account_code ?? '-',
      'dept_name'         => $deptName,

      // Data keuangan
      'uang_sebesar'      => $panjarAmount,
      'terbilang_uang'    => \App\Helpers\Terbilang::rupiah($panjarAmount),

      // Pengguna Anggaran = Pimpinan OPD (role kepala_opd)
      'approver_name'     => $pimpinan?->name ?? '................................',
      'approver_nip'      => $pimpinan?->nip,
      'approver_dept'     => $opdDept?->name ?? $deptName,

      // Bendahara Pengeluaran
      'bendahara_name'    => $bendahara?->name ?? '................................',
      'bendahara_nip'     => $bendahara?->nip,

      // QR & Misc
      'qr_image'          => $qrImage,
      'bku'               => null,
      'date'              => now()->translatedFormat('d F Y'),
    ];

    return \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.kuitansi_rampung', compact('sppd', 'targetUser', 'pdfData'))
      ->setPaper('f4', 'portrait')
      ->stream('KUITANSI_PANJAR-' . Str::slug($targetUser->name) . '.pdf');
  }

  /**
   * Stream PDF: Laporan Pengeluaran Riil
   */
  public function streamPengeluaranRiil(SppdRequest $sppd, Request $request)
  {
    $sppd->load([
      'user.department.parent',
      'user.department.head',
      'user.rank',
      'user.position',
      'pptk.rank',
      'actualExpenses',
      'approvals.approver'
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

    if (!$pimpinan && $opdDept?->id !== $department?->id) {
      $pimpinan = User::role('kepala_opd')
        ->where('department_id', $department?->id)
        ->where('is_active', true)
        ->first();
    }

    $lastApproval = $sppd->approvals()->reorder('step_order', 'desc')->first();
    $approver = $lastApproval?->approver;

    // QR Code
    $pdfUrl  = route('sppd.stream.pengeluaran-riil', ['sppd' => $sppd->id, 'user_id' => $userId]);
    $qrImage = QrSimulator::generate($pdfUrl);

    $pdfData = [
      'pptk_name'     => $pptk?->name ?? '_________________________',
      'pptk_nip'      => $pptk?->nip,
      'pimpinan_name' => $pimpinan?->name ?? '_________________________',
      'pimpinan_nip'  => $pimpinan?->nip,
      'pimpinan_role' => $pimpinan?->position?->name ?? 'Kepala Dinas',
      'is_walikota'   => ($approver && $approver->hasRole('walikota')) || ($pimpinan && $pimpinan->hasRole('walikota')),
      'qr_image'      => $qrImage,
    ];

    return \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.pengeluaran_riil', compact('sppd', 'targetUser', 'expenses', 'pdfData'))
      ->setPaper('f4', 'portrait')
      ->stream('PENGELUARAN_RILL-' . Str::slug($targetUser->name) . '.pdf');
  }

  /**
   * Stream PDF: Rincian Biaya Perjalanan Dinas
   */
  public function streamRincianBiaya(SppdRequest $sppd, Request $request)
  {
    $sppd->load([
      'user.department.parent',
      'user.rank',
      'user.position',
      'pptk.rank',
      'pptk.position',
      'costDetails',
      'actualExpenses',
      'advanceReceipts',
      'approvals.approver'
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

    if (!$pimpinan && $opdDept?->id !== $department?->id) {
      $pimpinan = User::role('kepala_opd')
        ->where('department_id', $department?->id)
        ->where('is_active', true)
        ->first();
    }

    // Bendahara Pengeluaran
    $bendahara = User::role('bendahara')
      ->where('department_id', $department?->id)
      ->where('is_active', true)
      ->first();

    // PPTK
    $pptk = $sppd->pptk;

    // QR Code
    $pdfUrl  = route('sppd.stream.rincian-biaya', ['sppd' => $sppd->id, 'user_id' => $userId]);
    $qrImage = QrSimulator::generate($pdfUrl);

    $pdfData = [
      'pptk_name'      => $pptk?->name ?? '_________________________',
      'pptk_nip'       => $pptk?->nip,
      'pimpinan_name'  => $pimpinan?->name ?? '_________________________',
      'pimpinan_nip'   => $pimpinan?->nip,
      'pimpinan_role'  => $pimpinan?->position?->name ?? 'Kepala Dinas',
      'bendahara_name' => $bendahara?->name ?? '_________________________',
      'bendahara_nip'  => $bendahara?->nip,
      'panjar_amount'  => $panjarAmount,
      'qr_image'       => $qrImage,
    ];

    return \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.rincian_biaya', compact('sppd', 'targetUser', 'costs', 'totalPengeluaranRiil', 'pdfData'))
      ->setPaper('f4', 'portrait')
      ->stream('RBPD-' . Str::slug($targetUser->name) . '.pdf');
  }
}
