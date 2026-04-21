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

class User extends Authenticatable
{
  /** @use HasFactory<UserFactory> */
  use HasFactory, HasRoles, LogsActivity, Notifiable;

  protected $fillable = [
    'name',
    'username',
    'nip',
    'email',
    'password',
    'phone',
    'employee_type',
    'department_id',
    'rank_id',
    'position_id',
    'position_name',
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
    ];
  }

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logOnly(['name', 'username', 'email', 'employee_type', 'department_id', 'is_active'])
      ->logOnlyDirty();
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

  public function departmentAssignments(): HasMany
  {
    return $this->hasMany(UserDepartmentAssignment::class);
  }

  public function sppdRequests(): HasMany
  {
    return $this->hasMany(SppdRequest::class);
  }

  public function createdSppdRequests(): HasMany
  {
    return $this->hasMany(SppdRequest::class, 'creator_id');
  }

  public function bankAccounts(): HasMany
  {
    return $this->hasMany(BankAccount::class);
  }

  public function approvals(): HasMany
  {
    return $this->hasMany(SppdApproval::class, 'approver_id');
  }

  public function digitalSignatures(): HasMany
  {
    return $this->hasMany(SppdDigitalSignature::class, 'signer_id');
  }
}
