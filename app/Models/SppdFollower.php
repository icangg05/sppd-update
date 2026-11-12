<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppdFollower extends Model
{
  /**
   * Jabatan perjalanan pengikut Inspektorat (susunan tim pengawasan APIP), urut dari yang
   * tertinggi. Dipakai untuk pilihan di form, validasi, dan urutan pengikut di SPT.
   */
  public const TRAVEL_POSITIONS = [
    'Penanggung Jawab',
    'Pembantu Penanggung Jawab',
    'Pengendali Teknis',
    'Ketua Tim',
    'Anggota',
    'Admin Tim',
  ];

  /** Peringkat jabatan perjalanan (0 = tertinggi); kosong atau tidak dikenal paling akhir. */
  public static function travelPositionRank(?string $position): int
  {
    $needle = mb_strtolower(trim((string) $position));
    foreach (self::TRAVEL_POSITIONS as $rank => $name) {
      if (mb_strtolower($name) === $needle) {
        return $rank;
      }
    }

    return PHP_INT_MAX;
  }

  protected $fillable = [
    'sppd_request_id',
    'user_id',
    'notes',
    'travel_position',
  ];

  public function sppdRequest(): BelongsTo
  {
    return $this->belongsTo(SppdRequest::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
