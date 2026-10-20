<?php

namespace App\Models;

use App\Enums\DepartmentType;
use App\Enums\PositionScope;
use App\Enums\PositionTier;
use App\Services\SppdWorkflowService;
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

  // Level (urutan) jabatan per role default — semakin kecil semakin tinggi.
  // Dipakai untuk mengurutkan pengikut di dokumen SPT.
  public const DEFAULT_LEVEL = 1000;

  private const ROLE_LEVELS = [
    'walikota'          => 1,
    'wakil_walikota'    => 2,
    'sekda'             => 3,
    'asisten'           => 10,
    'kepala_opd'        => 20,
    'sekwan'            => 20,
    'camat'             => 20,
    'lurah'             => 20,
    'kapus'             => 20,
    'sekretaris_opd'    => 30,
    'sekcam'            => 30,
    'kabid_irban_kabag' => 40,
    'kasubid_kasubag'   => 50,
  ];

  public static function levelForRole(?string $role): int
  {
    return self::ROLE_LEVELS[$role] ?? self::DEFAULT_LEVEL;
  }

  /** Cakupan keunikan jabatan untuk role tertentu (config role_uniqueness, lalu tingkat jabatannya). */
  public static function scopeForRole(string $role): PositionScope
  {
    return match (true) {
      in_array($role, config('role_uniqueness.system', []), true)     => PositionScope::SYSTEM,
      in_array($role, config('role_uniqueness.department', []), true) => PositionScope::DEPARTMENT,
      default                                                         => PositionTier::fromRole($role)->scope(),
    };
  }

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
   * Role yang berlaku bagi pemangku jabatan ini di unit kerja tertentu.
   * Role default hanya dipakai bila dikenal workflow aktif untuk jenis unit
   * tersebut (mis. Kabid/Irban/Kabag tidak dikenal alur Kecamatan, Kelurahan, dan Puskesmas); bila tidak,
   * pegawai memakai Staf agar tetap bisa mengajukan SPPD.
   */
  public function roleForDepartment(?Department $department): ?string
  {
    if (! $this->default_role) {
      return null;
    }

    // Jabatan kewilayahan di unit yang tidak sesuai (mis. Camat tercatat di
    // sebuah Dinas) tidak memberi role pimpinan — kemungkinan besar salah data.
    if (! $this->fitsDepartment($department)) {
      return 'staf';
    }

    return app(SppdWorkflowService::class)->roleUsedInWorkflows($this->default_role, $department?->type?->value)
      ? $this->default_role
      : 'staf';
  }

  /**
   * Jenis unit kerja yang wajib bagi jabatan kewilayahan (Camat/Lurah/Kapus
   * beserta sekretarisnya). Null bila jabatan boleh di unit mana pun.
   */
  public function requiredDepartmentType(): ?DepartmentType
  {
    $name = mb_strtolower(preg_replace('/\s+/', ' ', trim($this->name)));

    return match (true) {
      in_array($name, ['camat', 'sekretaris camat'], true) => DepartmentType::KECAMATAN,
      in_array($name, ['lurah', 'sekretaris lurah', 'sekretaris kelurahan'], true) => DepartmentType::KELURAHAN,
      str_starts_with($name, 'kepala puskesmas'), str_starts_with($name, 'kepala uptd puskesmas') => DepartmentType::PUSKESMAS,
      default => null,
    };
  }

  public function fitsDepartment(?Department $department): bool
  {
    $required = $this->requiredDepartmentType();

    return $required === null || $department?->type === $required;
  }

  /**
   * Tebak role sistem default dari nama jabatan. Dipakai untuk mengisi data
   * awal dan saran saat jabatan baru ditambahkan. Mengembalikan null bila
   * nama ambigu (mis. Kepala UPTD Terminal) agar Super Admin yang menentukan.
   *
   * Kelurahan, kecamatan, dan puskesmas diperlakukan workflow sebagai OPD
   * mandiri: kepalanya Kepala OPD dan sekretarisnya Sekretaris OPD.
   */
  public static function guessDefaultRole(string $name): ?string
  {
    $name = mb_strtolower(preg_replace('/\s+/', ' ', trim($name)));

    $exact = [
      'walikota'             => 'walikota',
      'wakil walikota'       => 'wakil_walikota',
      'sekretaris daerah'    => 'sekda',
      'sekretaris dewan'     => 'sekwan',
      'camat'                => 'kepala_opd',
      'sekretaris camat'     => 'sekretaris_opd',
      'lurah'                => 'kepala_opd',
      'sekretaris lurah'     => 'sekretaris_opd',
      'sekretaris kelurahan' => 'sekretaris_opd',
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
      'kepala puskesmas'                  => 'kepala_opd',
      'kepala uptd puskesmas'             => 'kepala_opd',
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
