<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppdReport extends Model
{
  protected $fillable = [
    'sppd_request_id',
    'report_text',
    'receipt_file',
    'documentation_file',
    'total_expense',
    'verification_status',
    'verified_by',
    'verified_at',
  ];

  protected function casts(): array
  {
    return [
      'total_expense' => 'decimal:2',
      'verification_status' => VerificationStatus::class,
      'verified_at' => 'datetime',
    ];
  }

  public function sppdRequest(): BelongsTo
  {
    return $this->belongsTo(SppdRequest::class);
  }

  public function verifier(): BelongsTo
  {
    return $this->belongsTo(User::class, 'verified_by');
  }

  /**
   * Mark the report as verified by a treasurer.
   */
  public function verify(int $verifierId): void
  {
    $this->update([
      'verification_status' => VerificationStatus::VERIFIED,
      'verified_by' => $verifierId,
      'verified_at' => now(),
    ]);
  }
}
