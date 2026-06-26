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
