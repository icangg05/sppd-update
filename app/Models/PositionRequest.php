<?php

namespace App\Models;

use App\Enums\PositionRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionRequest extends Model
{
  protected $fillable = [
    'requested_by',
    'department_id',
    'name',
    'reason',
    'status',
    'position_id',
    'reviewed_by',
    'reviewed_at',
    'review_note',
  ];

  protected function casts(): array
  {
    return [
      'status'      => PositionRequestStatus::class,
      'reviewed_at' => 'datetime',
    ];
  }

  public function requester(): BelongsTo
  {
    return $this->belongsTo(User::class, 'requested_by');
  }

  public function department(): BelongsTo
  {
    return $this->belongsTo(Department::class);
  }

  public function position(): BelongsTo
  {
    return $this->belongsTo(Position::class);
  }

  public function reviewer(): BelongsTo
  {
    return $this->belongsTo(User::class, 'reviewed_by');
  }

  public function scopePending(Builder $query): Builder
  {
    return $query->where('status', PositionRequestStatus::PENDING);
  }

  /**
   * Cari usulan jabatan yang masih menunggu dengan nama yang sama
   * (case-insensitive) untuk mencegah duplikasi pengajuan.
   */
  public static function pendingByName(string $name): ?self
  {
    return static::query()
      ->pending()
      ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
      ->first();
  }
}
