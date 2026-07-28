<?php

namespace App\Models;

use App\Enums\EmployeeType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Vinkla\Hashids\Facades\Hashids;

class User extends Authenticatable
{
  /** @use HasFactory<UserFactory> */
  use HasFactory, HasRoles, LogsActivity, Notifiable;

  protected $fillable = [
    'name',
    'username',
    'nik',
    'nip',
    'email',
    'password',
    'phone',
    'phone_verified',
    'whatsapp_notify',
    'changelog_seen_version',
    'employee_type',
    'department_id',
    'rank_id',
    'position_id',
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
      'employee_type' => EmployeeType::class,
      'is_active' => 'boolean',
      'phone_verified' => 'boolean',
      'whatsapp_notify' => 'boolean',
    ];
  }

  /** Boleh dikirimi notifikasi WhatsApp: nomor terverifikasi & notifikasi tidak dimatikan sendiri. */
  public function canReceiveWhatsApp(): bool
  {
    return ! empty($this->phone) && $this->phone_verified && $this->whatsapp_notify;
  }

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logOnly([
        'name', 'username', 'email', 'nip', 'nik', 'phone',
        'employee_type', 'department_id', 'rank_id', 'position_id',
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

  public function isDprdMember(): bool
  {
    return $this->employee_type === EmployeeType::DPRD
      || $this->hasRole(['anggota_dprd', 'pimpinan_dprd']);
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
