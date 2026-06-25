<?php

namespace App\Models;

use App\Enums\PositionScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
  protected $fillable = ['name', 'level', 'uniqueness_scope'];

  protected function casts(): array
  {
    return [
      'uniqueness_scope' => PositionScope::class,
    ];
  }

  public function users(): HasMany
  {
    return $this->hasMany(User::class);
  }

  /**
   * Cari jabatan berdasarkan nama secara case-insensitive.
   * Dipakai untuk memastikan jabatan unik / tidak duplikat.
   */
  public static function findByName(string $name): ?self
  {
    return static::query()
      ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
      ->first();
  }

  /**
   * Apakah jabatan ini dibatasi hanya boleh satu pemangku
   * (baik per OPD maupun seluruh sistem).
   */
  public function isSingleton(): bool
  {
    return ($this->uniqueness_scope ?? PositionScope::NONE) !== PositionScope::NONE;
  }

  /**
   * Cari pegawai aktif lain yang sudah memangku jabatan ini.
   * Mengembalikan null bila jabatan masih kosong / tidak dibatasi.
   *
   * @param  int|null  $departmentId  OPD yang diperiksa (untuk scope DEPARTMENT)
   * @param  int|null  $exceptUserId  Pegawai yang dikecualikan (saat edit)
   */
  public function conflictingHolder(?int $departmentId = null, ?int $exceptUserId = null): ?User
  {
    if (! $this->isSingleton()) {
      return null;
    }

    $query = $this->users()->where('is_active', true);

    if ($this->uniqueness_scope === PositionScope::DEPARTMENT) {
      $query->where('department_id', $departmentId);
    }

    if ($exceptUserId) {
      $query->where('id', '!=', $exceptUserId);
    }

    return $query->first();
  }
}
