<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\SignatureStatus;
use App\Enums\SppdStatus;
use App\Models\Budget;
use App\Models\SppdActualExpense;
use App\Models\SppdApproval;
use App\Models\SppdCostDetail;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
  public function index()
  {
    /** @var User $user */
    $user = Auth::user();

    // Tentukan archetype dashboard berdasarkan kewenangan (otomatis utk role baru).
    $archetype = match (true) {
      $user->hasAnyRole(['super_admin', 'admin_opd']) => 'admin',
      $user->can('sppd.approve')                      => 'leadership',
      default                                          => 'requester',
    };

    // ── DPA tahun berjalan (ditampilkan di semua archetype) ──
    $year = now()->year;
    $scopeIds = $this->budgetScopeIds($user);

    $budgetQuery = Budget::with('department')->where('year', $year);
    if ($scopeIds !== null) {
      $budgetQuery->whereIn('department_id', $scopeIds);
    }
    $budgets = $budgetQuery->get();

    $realByBudget = $this->realizationByBudget($budgets->pluck('id'));

    $pagu = (float) $budgets->sum('total_amount');
    $realisasi = (float) array_sum($realByBudget);
    $dpa = [
      'year'       => $year,
      'pagu'       => $pagu,
      'realisasi'  => $realisasi,
      'sisa'       => $pagu - $realisasi,
      'percentage' => $pagu > 0 ? round($realisasi / $pagu * 100, 2) : 0,
    ];

    // Daftar per-item (program/kegiatan) — angka diambil dari agregat massal,
    // jadi tetap murah. Hanya dikirim sebagai array skalar (bukan model Budget,
    // agar atribut realization yang berat tidak ikut ter-serialize).
    // Dibatasi ke 10 DPA terakhir dibuat agar daftar tetap ringkas (ringkasan
    // di atasnya tetap dihitung dari SELURUH budget, jadi total tak berubah).
    $dpaItems = $budgets->sortByDesc('created_at')->take(10)->map(function (Budget $b) use ($realByBudget) {
      $pagu = (float) $b->total_amount;
      $real = (float) ($realByBudget[$b->id] ?? 0);

      return [
        'id'         => $b->id,
        'name'       => $b->program ?: ($b->activity ?: $b->description),
        'activity'   => $b->activity,
        'account'    => $b->account_code,
        'department' => $b->department?->name,
        'pagu'       => $pagu,
        'realisasi'  => $real,
        'sisa'       => $pagu - $real,
        'percentage' => $pagu > 0 ? round($real / $pagu * 100, 2) : 0,
      ];
    })->values()->all();

    $shared = [
      'archetype' => $archetype,
      'dpa'       => $dpa,
      'dpaItems'  => $dpaItems,
    ];

    $specific = match ($archetype) {
      'admin'      => $this->adminData($user, $budgets, $realByBudget),
      'leadership' => $this->leadershipData($user),
      'requester'  => $this->requesterData($user),
    };

    return view('dashboard', array_merge($shared, $specific));
  }

  // ──────────────────────────────────────────────
  // Scope helpers
  // ──────────────────────────────────────────────

  /**
   * ID departemen dalam lingkup user (root OPD + seluruh turunannya).
   * null = seluruh sistem (super_admin).
   */
  private function budgetScopeIds(User $user): ?Collection
  {
    if ($user->hasRole('super_admin')) {
      return null;
    }

    return $user->department?->getScopeRootDepartment()->getScopedRelatedIds() ?? collect([0]);
  }

  /**
   * Query dasar SPPD sesuai lingkup: super_admin = semua,
   * admin/leadership = pengaju dalam lingkup OPD, requester = milik sendiri.
   */
  private function scopedSppd(User $user): Builder
  {
    if ($user->hasRole('super_admin')) {
      return SppdRequest::query();
    }

    $ids = $this->budgetScopeIds($user) ?? collect([0]);

    return SppdRequest::whereHas('user', fn ($q) => $q->whereIn('department_id', $ids));
  }

  /**
   * @return array<string, int>
   */
  private function statusCounts(Builder $base): array
  {
    return [
      'total'       => (clone $base)->count(),
      'in_progress' => (clone $base)->where('status', SppdStatus::IN_PROGRESS)->count(),
      'approved'    => (clone $base)->where('status', SppdStatus::APPROVED)->count(),
      'completed'   => (clone $base)->where('status', SppdStatus::COMPLETED)->count(),
      'rejected'    => (clone $base)->where('status', SppdStatus::REJECTED)->count(),
    ];
  }

  /**
   * Realisasi anggaran per budget_id secara massal (3 query, tanpa N+1).
   * Logika selaras dengan Budget::getRealizationAttribute().
   *
   * @param  Collection<int, int>  $budgetIds
   * @return array<int, float>  [budget_id => realisasi]
   */
  private function realizationByBudget(Collection $budgetIds): array
  {
    if ($budgetIds->isEmpty()) {
      return [];
    }

    // [sppd_request_id => budget_id] untuk SPPD yang sudah disetujui/selesai.
    $reqMap = SppdRequest::whereIn('budget_id', $budgetIds)
      ->whereIn('status', [SppdStatus::APPROVED->value, SppdStatus::COMPLETED->value])
      ->pluck('budget_id', 'id');

    if ($reqMap->isEmpty()) {
      return [];
    }

    $reqIds = $reqMap->keys();

    $costByReq = SppdCostDetail::whereIn('sppd_request_id', $reqIds)
      ->groupBy('sppd_request_id')
      ->selectRaw('sppd_request_id, SUM(total) as agg')
      ->pluck('agg', 'sppd_request_id');

    $expByReq = SppdActualExpense::whereIn('sppd_request_id', $reqIds)
      ->groupBy('sppd_request_id')
      ->selectRaw('sppd_request_id, SUM(amount) as agg')
      ->pluck('agg', 'sppd_request_id');

    $result = [];
    foreach ($reqMap as $reqId => $budgetId) {
      $result[$budgetId] = ($result[$budgetId] ?? 0)
        + (float) ($costByReq[$reqId] ?? 0)
        + (float) ($expByReq[$reqId] ?? 0);
    }

    return $result;
  }

  // ──────────────────────────────────────────────
  // Data per archetype
  // ──────────────────────────────────────────────

  /**
   * @param  Collection<int, Budget>  $budgets
   * @param  array<int, float>  $realByBudget
   */
  private function adminData(User $user, Collection $budgets, array $realByBudget): array
  {
    $base = $this->scopedSppd($user);
    $stats = $this->statusCounts($base);

    // Tren perjalanan disetujui per minggu (12 minggu terakhir, ter-scope).
    // Disetujui = status APPROVED atau COMPLETED (perjalanan yang sudah di-acc).
    $weeklyApproved = [];
    for ($i = 11; $i >= 0; $i--) {
      $start = now()->startOfWeek()->subWeeks($i);
      $end = (clone $start)->endOfWeek();
      $count = (clone $base)
        ->whereIn('status', [SppdStatus::APPROVED, SppdStatus::COMPLETED])
        ->whereBetween('created_at', [$start, $end])
        ->count();
      $weeklyApproved[] = ['week' => $start->translatedFormat('d M'), 'count' => $count];
    }

    $statusDistribution = [
      ['label' => 'Selesai',   'count' => $stats['approved'] + $stats['completed'], 'color' => '#10b981'],
      ['label' => 'Di Proses', 'count' => $stats['in_progress'], 'color' => '#3b82f6'],
      ['label' => 'Ditolak',   'count' => $stats['rejected'],    'color' => '#ef4444'],
    ];

    $recentSppd = (clone $base)
      ->with(['user', 'category', 'budget.department', 'destinations.regency', 'costDetails'])
      ->latest()->take(6)->get();

    $pendingApprovals = SppdApproval::with(['sppdRequest.user'])
      ->where('approver_id', $user->id)
      ->where('status', ApprovalStatus::PENDING)
      ->latest()->take(5)->get();

    // Top OPD berdasarkan % pemakaian anggaran (dari budget yang sudah dimuat).
    $topByUsage = $budgets
      ->groupBy('department_id')
      ->map(function ($group) use ($realByBudget) {
        $pagu = (float) $group->sum('total_amount');
        $real = (float) $group->sum(fn ($b) => $realByBudget[$b->id] ?? 0);

        return [
          'name'       => $group->first()->department?->name ?? '-',
          'pagu'       => $pagu,
          'realisasi'  => $real,
          'percentage' => $pagu > 0 ? round($real / $pagu * 100, 2) : 0,
        ];
      })
      ->sortByDesc('percentage')
      ->take(6)
      ->values();

    return compact('stats', 'weeklyApproved', 'statusDistribution', 'recentSppd', 'pendingApprovals', 'topByUsage');
  }

  private function leadershipData(User $user): array
  {
    $base = $this->scopedSppd($user);
    $stats = $this->statusCounts($base);

    $pendingApprovals = SppdApproval::with(['sppdRequest.user', 'sppdRequest.destinations.regency', 'sppdRequest.costDetails'])
      ->where('approver_id', $user->id)
      ->where('status', ApprovalStatus::PENDING)
      ->latest()->get();

    $pendingSignatures = collect();
    if ($user->can('sppd.sign')) {
      $pendingSignatures = $user->digitalSignatures()
        ->with('sppdRequest.user')
        ->where('status', SignatureStatus::PENDING)
        ->latest()->get();
    }

    $recentDecisions = SppdApproval::with(['sppdRequest.user'])
      ->where('approver_id', $user->id)
      ->whereIn('status', [ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])
      ->latest()->take(5)->get();

    return compact('stats', 'pendingApprovals', 'pendingSignatures', 'recentDecisions');
  }

  private function requesterData(User $user): array
  {
    $base = SppdRequest::where('user_id', $user->id);
    $stats = $this->statusCounts($base);

    $mySppd = (clone $base)
      ->with(['destinations.regency', 'costDetails'])
      ->latest()->take(6)->get();

    // SPPD selesai milik user yang laporannya belum dibuat.
    $needReport = (clone $base)
      ->where('status', SppdStatus::COMPLETED)
      ->whereDoesntHave('report')
      ->with(['destinations.regency'])
      ->latest()->take(5)->get();

    return compact('stats', 'mySppd', 'needReport');
  }
}
