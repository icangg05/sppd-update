<?php

namespace App\Models;

use App\Enums\PositionScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Vinkla\Hashids\Facades\Hashids;

class Position extends Model
{
  use LogsActivity;

  protected $fillable = ['name', 'level', 'uniqueness_scope', 'default_role', 'created_by'];

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logOnly(['name', 'level', 'uniqueness_scope', 'default_role'])
      ->logOnlyDirty()
      ->dontLogEmptyChanges()
      ->setDescriptionForEvent(fn (string $event) => $this->describeLogEvent($event));
  }

  protected function describeLogEvent(string $event): string
  {
    $label = $this->name ? "Jabatan {$this->name}" : "Jabatan #{$this->getKey()}";

    return match ($event) {
      'created' => "{$label} dibuat",
      'updated' => "{$label} diperbarui",
      'deleted' => "{$label} dihapus",
      default   => "{$label} {$event}",
    };
  }

  /**
   * ID jabatan dalam bentuk hashids — dipakai pada query string (mis. filter
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
   * Pegawai yang menambahkan jabatan ini (null = bawaan sistem).
   */
  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  /**
   * Super Admin boleh mengelola semua jabatan; Admin OPD hanya jabatan yang
   * ditambahkan oleh akun di OPD yang sama (agar sesama admin bisa saling
   * membetulkan dan jabatan tidak "terkunci" saat akun admin berganti).
   */
  public function isManageableBy(User $user): bool
  {
    if ($user->hasRole('super_admin')) {
      return true;
    }

    return $user->hasRole('admin_opd')
      && $user->department_id !== null
      && $this->creator !== null
      && (int) $this->creator->department_id === (int) $user->department_id;
  }

  /**
   * Tebak role sistem default dari nama jabatan. Dipakai untuk mengisi data
   * awal dan saran saat jabatan baru ditambahkan. Mengembalikan null bila
   * nama ambigu (mis. Kepala UPTD Terminal) agar Super Admin yang menentukan.
   */
  public static function guessDefaultRole(string $name): ?string
  {
    $name = mb_strtolower(preg_replace('/\s+/', ' ', trim($name)));

    $exact = [
      'walikota'             => 'walikota',
      'wakil walikota'       => 'wakil_walikota',
      'sekretaris daerah'    => 'sekda',
      'sekretaris dewan'     => 'sekwan',
      'camat'                => 'camat',
      'sekretaris camat'     => 'sekcam',
      'lurah'                => 'lurah',
      'sekretaris dinas'     => 'sekretaris_opd',
      'sekretaris badan'     => 'sekretaris_opd',
      'sekretaris inspektur' => 'sekretaris_opd',
      'inspektur'            => 'kepala_opd',
      'inspektur kota'       => 'kepala_opd',
      'direktur rsud'        => 'kepala_opd',
    ];

    if (array_key_exists($name, $exact)) {
      return $exact[$name];
    }

    // Dicek berurutan — awalan yang lebih spesifik harus di atas yang umum.
    $prefixes = [
      'asisten apoteker'                  => 'staf',
      'asisten '                          => 'asisten',
      'kepala puskesmas'                  => 'kapus',
      'kepala uptd puskesmas'             => 'kapus',
      'sekretaris dinas '                 => 'sekretaris_opd',
      'sekretaris badan '                 => 'sekretaris_opd',
      'kepala dinas'                      => 'kepala_opd',
      'kepala badan'                      => 'kepala_opd',
      'kepala pelaksana bpbd'             => 'kepala_opd',
      'kepala satuan polisi pamong praja' => 'kepala_opd',
      'kepala sub bagian'                 => 'kasubid_kasubag',
      'kepala subbagian'                  => 'kasubid_kasubag',
      'kepala sub bidang'                 => 'kasubid_kasubag',
      'kepala subbidang'                  => 'kasubid_kasubag',
      'kepala seksi'                      => 'kasubid_kasubag',
      'kepasi seksi'                      => 'kasubid_kasubag',
      'kepala bidang'                     => 'kabid_irban_kabag',
      'kepala bagian'                     => 'kabid_irban_kabag',
      'inspektur pembantu'                => 'kabid_irban_kabag',
      'kepala '                           => null,
      'kepasi '                           => null,
      'sekretaris '                       => null,
    ];

    foreach ($prefixes as $prefix => $role) {
      if (str_starts_with($name, $prefix)) {
        return $role;
      }
    }

    return 'staf';
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
