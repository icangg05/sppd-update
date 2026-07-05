<?php

namespace App\Livewire\Leadership;

use App\Enums\SppdStatus;
use App\Livewire\Concerns\ScopesSppd;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Rekap & Statistik SPPD dalam lingkup OPD pejabat yang login:
 * ringkasan status, tren 12 bulan, distribusi status, dan unit tersibuk.
 */
#[Layout('layouts.app')]
class SppdRecap extends Component
{
  use ScopesSppd;

  /** @return array<string, int> */
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

  public function render()
  {
    /** @var \App\Models\User $user */
    $user = Auth::user();
    $base = $this->scopedSppd($user);

    $stats = $this->statusCounts($base);

    // Tren 12 bulan (masuk vs selesai) — ter-scope.
    $monthlyTrend = [];
    for ($i = 11; $i >= 0; $i--) {
      $date = now()->subMonths($i);
      $masuk = (clone $base)
        ->whereYear('created_at', $date->year)
        ->whereMonth('created_at', $date->month)
        ->count();
      $selesai = (clone $base)
        ->whereYear('created_at', $date->year)
        ->whereMonth('created_at', $date->month)
        ->where('status', SppdStatus::COMPLETED)
        ->count();
      $monthlyTrend[] = ['month' => $date->translatedFormat('M'), 'masuk' => $masuk, 'selesai' => $selesai];
    }

    $statusDistribution = [
      ['label' => 'Selesai',      'count' => $stats['completed'],   'color' => 'bg-emerald-500'],
      ['label' => 'Disetujui',    'count' => $stats['approved'],    'color' => 'bg-green-500'],
      ['label' => 'Dalam Proses', 'count' => $stats['in_progress'], 'color' => 'bg-blue-500'],
      ['label' => 'Ditolak',      'count' => $stats['rejected'],    'color' => 'bg-rose-500'],
    ];

    // Unit kerja tersibuk (jumlah SPPD terbanyak) dalam lingkup.
    $topUnits = (clone $base)
      ->join('users', 'sppd_requests.user_id', '=', 'users.id')
      ->join('departments', 'users.department_id', '=', 'departments.id')
      ->selectRaw('departments.name as unit, COUNT(*) as jumlah')
      ->groupBy('departments.name')
      ->orderByDesc('jumlah')
      ->limit(6)
      ->get();

    return view('livewire.leadership.sppd-recap', [
      'stats'              => $stats,
      'monthlyTrend'       => $monthlyTrend,
      'statusDistribution' => $statusDistribution,
      'topUnits'           => $topUnits,
    ])->title('Rekap & Statistik SPPD');
  }
}
