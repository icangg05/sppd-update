<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Vinkla\Hashids\Facades\Hashids;

class User extends Authenticatable
{
  /** @use HasFactory<UserFactory> */
  use HasFactory, HasRoles, LogsActivity, Notifiable;

  /** Role Anggota DPRD — satu-satunya penanda pegawai DPRD. */
  public const DPRD_ROLES = ['anggota_dprd', 'pimpinan_dprd'];

  /** Prefiks kunci sesi untuk cache hasil usesDefaultPassword() per pengguna. */
  private const DEFAULT_PASSWORD_SESSION_KEY = 'uses_default_password.';

  protected $fillable = [
    'name',
    'username',
    'nik',
    'nip',
    'email',
    'password',
    'telegram_chat_id',
    'telegram_username',
    'telegram_linked_at',
    'telegram_notify',
    'changelog_seen_version',
    'department_id',
    'rank_id',
    'position_id',
    'atasan_id',
    'dprd_jabatan',
    'partai',
    'photo',
    'is_active',
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
      'is_active' => 'boolean',
      'failed_login_attempts' => 'integer',
      'login_locked_until' => 'datetime',
      'login_blocked_at' => 'datetime',
      'telegram_linked_at' => 'datetime',
      'telegram_notify' => 'boolean',
    ];
  }

  /** Boleh dikirimi notifikasi Telegram: akun sudah terhubung & notifikasi tidak dimatikan sendiri. */
  public function canReceiveTelegram(): bool
  {
    return filled($this->telegram_chat_id) && $this->telegram_notify;
  }

  /** Diblokir dari login karena berulang kali salah password; hanya super_admin yang dapat membuka. */
  public function isLoginBlocked(): bool
  {
    return $this->login_blocked_at !== null;
  }

  /** Masih memakai password bawaan (config users.default_password) yang diberikan saat akun dibuat. */
  public function usesDefaultPassword(): bool
  {
    return Hash::check(config('users.default_password'), $this->password);
  }

  /**
   * Sama seperti usesDefaultPassword(), tapi hasilnya disimpan di sesi agar
   * pengecekan bcrypt (lambat) tidak berjalan di setiap pemuatan halaman.
   */
  public function usesDefaultPasswordCached(): bool
  {
    return session()->remember(self::DEFAULT_PASSWORD_SESSION_KEY . $this->id, fn () => $this->usesDefaultPassword());
  }

  /** Lupakan hasil cache usesDefaultPasswordCached(), mis. setelah password diganti. */
  public function forgetDefaultPasswordCache(): void
  {
    session()->forget(self::DEFAULT_PASSWORD_SESSION_KEY . $this->id);
  }

  /** Buka blokir login dan reset hitungan percobaan gagal. */
  public function resetLoginAttempts(): void
  {
    $this->forceFill([
      'failed_login_attempts' => 0,
      'login_locked_until' => null,
      'login_blocked_at' => null,
    ])->save();
  }

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logOnly([
        'name', 'username', 'email', 'nip', 'nik',
        'department_id', 'rank_id', 'position_id', 'atasan_id',
        'dprd_jabatan', 'partai', 'is_active',
      ])
      ->logOnlyDirty()
      ->dontLogEmptyChanges();
  }

  // ──────────────────────────────────────────────
  // Relationships
  // ──────────────────────────────────────────────

  public function department(): BelongsTo
  {
    return $this->belongsTo(Department::class);
  }

  public function rank(): BelongsTo
  {
    return $this->belongsTo(Rank::class);
  }

  public function position(): BelongsTo
  {
    return $this->belongsTo(Position::class);
  }

  /** Atasan langsung: rantainya menentukan pejabat tahap persetujuan SPPD. */
  public function atasan(): BelongsTo
  {
    return $this->belongsTo(self::class, 'atasan_id');
  }

  /** Pegawai yang atasan langsungnya pegawai ini. */
  public function bawahan(): HasMany
  {
    return $this->hasMany(self::class, 'atasan_id');
  }

  public function sppdRequests(): HasMany
  {
    return $this->hasMany(SppdRequest::class);
  }

  public function createdSppdRequests(): HasMany
  {
    return $this->hasMany(SppdRequest::class, 'creator_id');
  }

  public function approvals(): HasMany
  {
    return $this->hasMany(SppdApproval::class, 'approver_id');
  }

  public function digitalSignatures(): HasMany
  {
    return $this->hasMany(SppdDigitalSignature::class, 'signer_id');
  }

  /** Anggota DPRD = pemegang salah satu DPRD_ROLES. */
  public function isDprdMember(): bool
  {
    return $this->hasRole(self::DPRD_ROLES);
  }

  /**
   * Pegawai aktif lain yang sudah memegang role kewenangan tunggal
   * (config role_uniqueness: per sistem / per unit kerja). Null bila role
   * boleh dipegang banyak pegawai atau masih kosong.
   */
  public static function roleConflictHolder(string $role, ?int $departmentId, ?int $exceptUserId = null): ?self
  {
    $perOpd = in_array($role, config('role_uniqueness.department', []), true);
    $perSystem = in_array($role, config('role_uniqueness.system', []), true);

    if (! $perOpd && ! $perSystem) {
      return null;
    }

    return static::role($role)
      ->where('is_active', true)
      ->when($perOpd, fn ($q) => $q->where('department_id', $departmentId))
      ->when($exceptUserId, fn ($q) => $q->where('id', '!=', $exceptUserId))
      ->first();
  }

  /**
   * Get the value of the model's route key.
   */
  public function getRouteKey(): string
  {
    return Hashids::encode($this->getKey());
  }

  /**
   * Retrieve the model for a bound value.
   *
   * @param  mixed  $value
   * @param  string|null  $field
   * @return \Illuminate\Database\Eloquent\Model|null
   */
  public function resolveRouteBinding($value, $field = null)
  {
    $decoded = Hashids::decode($value);

    if (empty($decoded)) {
      abort(404);
    }

    return $this->where($field ?? $this->getRouteKeyName(), $decoded[0])->firstOrFail();
  }
}
