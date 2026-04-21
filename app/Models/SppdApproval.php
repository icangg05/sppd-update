<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
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
  ];

  protected function casts(): array
  {
    return [
      'step_order' => 'integer',
      'status' => ApprovalStatus::class,
      'acted_at' => 'datetime',
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
   * Mark this approval step as approved.
   */
  public function approve(?string $notes = null): void
  {
    $this->update([
      'status' => ApprovalStatus::APPROVED,
      'acted_at' => now(),
      'notes' => $notes,
    ]);
  }

  /**
   * Mark this approval step as rejected.
   */
  public function reject(?string $notes = null): void
  {
    $this->update([
      'status' => ApprovalStatus::REJECTED,
      'acted_at' => now(),
      'notes' => $notes,
    ]);
  }
}
