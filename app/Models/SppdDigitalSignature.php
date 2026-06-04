<?php

namespace App\Models;

use App\Enums\SignatureStatus;
use App\Models\SppdRequest;
use App\Models\User;
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
    'document_type',
    'provider_id',
    'provider_name',
    'error_message',
    'signed_file_path',
    'qr_code_data',
    'sign_page',
    'sign_x',
    'sign_y',
    'sign_width',
    'sign_height',
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

  public function markError(string $message): void
  {
    $this->update([
      'status' => SignatureStatus::REJECTED,
      'error_message' => $message,
    ]);
  }

  /**
   * Scope a query to only include signatures that are processing.
   */
  public function scopeProcessing($query)
  {
    return $query->where('status', SignatureStatus::PROCESSING);
  }

  /**
   * Mark this signature as processing.
   */
  public function markProcessing(): void
  {
    $this->update([
      'status' => SignatureStatus::PROCESSING,
      'error_message' => null,
    ]);
  }

  public function getSignedFileUrlAttribute(): ?string
  {
    if (! $this->signed_file_path) {
      return null;
    }

    return \Illuminate\Support\Facades\Storage::disk(config('tte.storage.disk'))->url($this->signed_file_path);
  }
}
