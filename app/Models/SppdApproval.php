<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppdApproval extends Model
{
  protected $fillable = [
    'sppd_request_id',
    'approver_id',
    'role_label',
    'step_order',
    'status',
    'acted_at',
    'notes',
    'signs_spt',
    'signs_sppd',
  ];

  protected function casts(): array
  {
    return [
      'step_order' => 'integer',
      'status'     => ApprovalStatus::class,
      'acted_at'   => 'datetime',
      'signs_spt'  => 'boolean',
      'signs_sppd' => 'boolean',
    ];
  }

  public function sppdRequest(): BelongsTo
  {
    return $this->belongsTo(SppdRequest::class);
  }

  public function approver(): BelongsTo
  {
    return $this->belongsTo(User::class, 'approver_id');
  }

  /**
   * Pending approvals where it is the approver's turn (all prior steps approved).
   */
  public function scopeReadyForApprover(Builder $query, int $approverId): Builder
  {
    return $query
      ->where('approver_id', $approverId)
      ->where('status', ApprovalStatus::PENDING)
      ->whereNotExists(function ($sub) {
        $sub->from('sppd_approvals as prev')
          ->whereColumn('prev.sppd_request_id', 'sppd_approvals.sppd_request_id')
          ->whereColumn('prev.step_order', '<', 'sppd_approvals.step_order')
          ->where('prev.status', '!=', ApprovalStatus::APPROVED->value);
      });
  }

  /**
   * Mark this approval step as approved.
   */
  public function approve(?string $notes = null): void
  {
    $this->update([
      'status'   => ApprovalStatus::APPROVED,
      'acted_at' => now(),
      'notes'    => $notes,
    ]);
  }

  /**
   * Mark this approval step as rejected.
   */
  public function reject(?string $notes = null): void
  {
    $this->update([
      'status'   => ApprovalStatus::REJECTED,
      'acted_at' => now(),
      'notes'    => $notes,
    ]);
  }
}
