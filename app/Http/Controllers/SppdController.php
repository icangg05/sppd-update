<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Models\Budget;
use App\Models\Province;
use App\Models\Regency;
use App\Models\SppdApproval;
use App\Models\SppdCategory;
use App\Models\SppdCostDetail;
use App\Models\SppdDestination;
use App\Models\SppdFollower;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
    $categories = SppdCategory::all();
    $budgets    = Budget::with('department')->get();
    $provinces  = Province::orderBy('name')->get();
    $users      = User::where('is_active', true)->orderBy('name')->get();

    return view('sppd.create', compact('categories', 'budgets', 'provinces', 'users'));
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

    try {
      DB::transaction(function () use ($validated, $request) {
        $sppd = SppdRequest::create([
          'user_id'         => $validated['user_id'],
          'creator_id'      => Auth::id(),
          'budget_id'       => $validated['budget_id'],
          'category_id'     => $validated['category_id'],
          'purpose'         => $validated['purpose'],
          'problem'         => $validated['problem'],
          'facts'           => $validated['facts'],
          'analysis'        => $validated['analysis'],
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
        ]);

        // Save destinations
        foreach ($validated['destinations'] as $dest) {
          if ($sppd->domain->value === 'dalam_daerah') {
            // Set default to Sulawesi Tenggara and Kota Kendari
            $sultra = \App\Models\Province::where('name', 'Sulawesi Tenggara')->first();
            $kendari = \App\Models\Regency::where('name', 'LIKE', '%Kendari%')
              ->where('province_id', $sultra?->id)
              ->first();

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

        // Save cost details
        if (!empty($validated['costs'])) {
          foreach ($validated['costs'] as $cost) {
            $sppd->costDetails()->create([
              'user_id'     => $sppd->user_id,
              'description' => $cost['description'],
              'unit_cost'   => $cost['unit_cost'],
              'quantity'    => $cost['quantity'],
            ]);
          }
        }

        // Generate approvals via dynamic workflow
        $workflowService = app(\App\Services\SppdWorkflowService::class);
        $success = $workflowService->generateApprovals($sppd);

        if (!$success) {
          throw new \Exception('Sistem belum memiliki aturan Workflow untuk instansi, peran pemohon, atau tujuan tersebut. Silakan hubungi admin untuk melengkapi aturan Workflow SPPD.');
        }
      });

      return redirect()->route('sppd.index')->with('success', 'SPPD berhasil dibuat dan diajukan.');
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
  public function destroy(SppdRequest $sppd)
  {
    $isOwner = Auth::id() === $sppd->creator_id || Auth::id() === $sppd->user_id;
    if (!$isOwner) {
      return back()->with('error', 'Anda tidak memiliki akses untuk menghapus SPPD ini.');
    }

    if ($sppd->status !== SppdStatus::DRAFT && $sppd->status !== SppdStatus::IN_PROGRESS) {
      return back()->with('error', 'SPPD yang sudah diproses tidak dapat dihapus.');
    }

    // If in progress, check if any approval has been made
    if ($sppd->status === SppdStatus::IN_PROGRESS) {
      $hasApproval = $sppd->approvals()->where('status', '!=', ApprovalStatus::PENDING)->exists();
      if ($hasApproval) {
        return back()->with('error', 'SPPD tidak dapat dihapus karena sudah ada pejabat yang memberikan persetujuan/penolakan.');
      }
    }

    $sppd->delete();

    return redirect()->route('sppd.index')->with('success', 'SPPD berhasil dihapus/dibatalkan.');
  }
}
