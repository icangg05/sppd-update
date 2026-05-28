<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\SppdStatus;
use App\Models\Budget;
use App\Models\SppdApproval;
use App\Models\SppdRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
  public function index()
  {
    $user = Auth::user();

    // Stats - match design: Total SPPD, Telaah Masuk, Di Proses, Selesai
    $stats = [
      'total'       => SppdRequest::count(),
      'in_progress' => SppdRequest::where('status', SppdStatus::IN_PROGRESS)->count(),
      'approved'    => SppdRequest::where('status', SppdStatus::APPROVED)->count(),
      'completed'   => SppdRequest::where('status', SppdStatus::COMPLETED)->count(),
      'rejected'    => SppdRequest::where('status', SppdStatus::REJECTED)->count(),
    ];

    // Recent SPPD (for telaah terbaru)
    $recentSppd = SppdRequest::with(['user', 'category', 'budget.department', 'destinations'])
      ->latest()
      ->take(5)
      ->get();

    // Pending approvals for current user
    $pendingApprovals = SppdApproval::with(['sppdRequest.user', 'sppdRequest.category'])
      ->where('approver_id', $user->id)
      ->where('status', ApprovalStatus::PENDING)
      ->latest()
      ->take(5)
      ->get();

    // Budget Stats for Chart
    $budgetQuery = Budget::query();
    if (!$user->hasRole('super_admin')) {
      $budgetQuery->where('department_id', $user->department_id);
    }
    $budgets = $budgetQuery->get();

    // Total anggaran tahun berjalan
    $totalBudget = $budgets->sum('total_amount');

    // Monthly trend data (Masuk vs Selesai) for last 12 months
    $monthlyTrend = [];
    for ($i = 11; $i >= 0; $i--) {
      $date = now()->subMonths($i);
      $month = $date->format('M');
      $year = $date->year;
      $monthNum = $date->month;

      $masuk = SppdRequest::whereYear('created_at', $year)
        ->whereMonth('created_at', $monthNum)
        ->count();
      $selesai = SppdRequest::whereYear('created_at', $year)
        ->whereMonth('created_at', $monthNum)
        ->where('status', SppdStatus::COMPLETED)
        ->count();

      $monthlyTrend[] = [
        'month' => $month,
        'masuk' => $masuk,
        'selesai' => $selesai,
      ];
    }

    // Status distribution for donut chart
    $statusDistribution = [
      ['label' => 'Selesai', 'count' => $stats['completed'], 'color' => '#10b981'],
      ['label' => 'Di Proses', 'count' => $stats['in_progress'], 'color' => '#3b82f6'],
      ['label' => 'Masuk', 'count' => $stats['in_progress'], 'color' => '#f59e0b'],
      ['label' => 'Perbaikan', 'count' => $stats['rejected'], 'color' => '#8b5cf6'],
      ['label' => 'Tidak Diterima', 'count' => 0, 'color' => '#ef4444'],
    ];

    // Top 6 OPD pengaju SPPD
    $topDepartments = SppdRequest::select('budget_id', DB::raw('count(*) as total'))
      ->groupBy('budget_id')
      ->orderByDesc('total')
      ->take(6)
      ->get()
      ->map(function ($item) {
        $budget = Budget::with('department')->find($item->budget_id);
        return [
          'name' => $budget?->department?->name ?? ($budget?->name ?? 'Unknown'),
          'total' => $item->total,
        ];
      });

    return view('dashboard', compact(
      'stats',
      'recentSppd',
      'pendingApprovals',
      'budgets',
      'totalBudget',
      'monthlyTrend',
      'statusDistribution',
      'topDepartments'
    ));
  }
}
