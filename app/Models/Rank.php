<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class Rank extends Model
{
  protected $fillable = ['name', 'group'];

  public function users(): HasMany
  {
    return $this->hasMany(User::class);
  }

  /**
   * Bobot golongan untuk pengurutan (semakin besar semakin tinggi). PNS
   * "III/b" = 32; PPPK I–XVII disetarakan dengan golongan PNS (mis. IX ≈ III/a).
   */
  public function weight(): int
  {
    $roman = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9,
      'X' => 10, 'XI' => 11, 'XII' => 12, 'XIII' => 13, 'XIV' => 14, 'XV' => 15, 'XVI' => 16, 'XVII' => 17];
    $group = strtoupper(trim((string) $this->group));

    // PNS: golongan/ruang, mis. "IV/a".
    if (preg_match('/^(I{1,3}|IV)\/([A-E])$/', $group, $m)) {
      return $roman[$m[1]] * 10 + (ord($m[2]) - 64);
    }

    // PPPK: I–IV ≈ gol. I, V–VIII ≈ II, IX–XII ≈ III, XIII–XVII ≈ IV.
    $n = $roman[$group] ?? 0;

    return match (true) {
      $n >= 13 => 40 + ($n - 12),
      $n >= 9  => 30 + ($n - 8),
      $n >= 5  => 20 + ($n - 4),
      $n >= 1  => 10 + $n,
      default  => 0,
    };
  }

  /**
   * ID pangkat dalam bentuk hashids — dipakai pada query string (mis. filter
   * pegawai) agar ID asli tidak terekspos di URL.
   */
  public function hashid(): string
  {
    return Hashids::encode($this->getKey());
  }

  /**
   * Decode hashids menjadi ID asli. Mengembalikan null bila tidak valid.
   */
  public static function decodeHashid(?string $hash): ?int
  {
    $decoded = $hash ? Hashids::decode($hash) : [];

    return $decoded[0] ?? null;
  }
}
