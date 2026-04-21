<?php

namespace App\Models;

use App\Enums\SignatureStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppdDigitalSignature extends Model
{
  protected $fillable = [
    'sppd_request_id',
    'signer_id',
    'status',
    'signed_at',
    'signature_data',
    'certificate_serial',
  ];

  protected function casts(): array
  {
    return [
      'status' => SignatureStatus::class,
      'signed_at' => 'datetime',
    ];
  }

  public function sppdRequest(): BelongsTo
  {
    return $this->belongsTo(SppdRequest::class);
  }

  public function signer(): BelongsTo
  {
    return $this->belongsTo(User::class, 'signer_id');
  }

  /**
   * Mark this signature as signed.
   */
  public function sign(?string $signatureData = null, ?string $certificateSerial = null): void
  {
    $this->update([
      'status' => SignatureStatus::SIGNED,
      'signed_at' => now(),
      'signature_data' => $signatureData,
      'certificate_serial' => $certificateSerial,
    ]);
  }
}
