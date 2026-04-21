<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\SppdStatus;
use App\Models\SppdApproval;
use App\Models\SppdRequest;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
  public function index()
  {
    $user = Auth::user();

    // Stats
    $stats = [
      'total'       => SppdRequest::count(),
      'draft'       => SppdRequest::where('status', SppdStatus::DRAFT)->count(),
      'in_progress' => SppdRequest::where('status', SppdStatus::IN_PROGRESS)->count(),
      'approved'    => SppdRequest::where('status', SppdStatus::APPROVED)->count(),
      'completed'   => SppdRequest::where('status', SppdStatus::COMPLETED)->count(),
      'rejected'    => SppdRequest::where('status', SppdStatus::REJECTED)->count(),
    ];

    // Recent SPPD
    $recentSppd = SppdRequest::with(['user', 'category', 'budget.department'])
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

    return view('dashboard', compact('stats', 'recentSppd', 'pendingApprovals'));
  }
}
