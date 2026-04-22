<?php

namespace App\Models;

use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class SppdRequest extends Model
{
  use LogsActivity;

  protected $fillable = [
    'user_id',
    'creator_id',
    'pptk_id',
    'budget_id',
    'category_id',
    'purpose',
    'problem',
    'facts',
    'analysis',
    'start_date',
    'end_date',
    'transport_type',
    'transport_name',
    'departure_place',
    'domain',
    'urgency',
    'status',
    'document_number',
    'notes',
    'sppd_date',
    'spt_date',
    'is_secretariat',
  ];

  protected function casts(): array
  {
    return [
      'start_date' => 'date',
      'end_date' => 'date',
      'sppd_date' => 'date',
      'spt_date' => 'date',
      'domain' => SppdDomain::class,
      'status' => SppdStatus::class,
      'is_secretariat' => 'boolean',
    ];
  }

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logOnly(['status', 'document_number'])
      ->logOnlyDirty();
  }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

  /** Pelaksana perjalanan */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /** Pembuat draft */
  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'creator_id');
  }

  /** PPTK penanggungjawab */
  public function pptk(): BelongsTo
  {
    return $this->belongsTo(User::class, 'pptk_id');
  }

  public function budget(): BelongsTo
  {
    return $this->belongsTo(Budget::class);
  }

  public function category(): BelongsTo
  {
    return $this->belongsTo(SppdCategory::class, 'category_id');
  }

  public function destinations(): HasMany
  {
    return $this->hasMany(SppdDestination::class);
  }

  public function followers(): HasMany
  {
    return $this->hasMany(SppdFollower::class);
  }

  public function approvals(): HasMany
  {
    return $this->hasMany(SppdApproval::class)->orderBy('step_order');
  }

  public function costDetails(): HasMany
  {
    return $this->hasMany(SppdCostDetail::class);
  }

  public function actualExpenses(): HasMany
  {
    return $this->hasMany(SppdActualExpense::class);
  }

  public function advanceReceipts(): HasMany
  {
    return $this->hasMany(SppdAdvanceReceipt::class);
  }

  public function report(): HasOne
  {
    return $this->hasOne(SppdReport::class);
  }

  public function digitalSignatures(): HasMany
  {
    return $this->hasMany(SppdDigitalSignature::class);
  }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

  /**
   * Get the current active approval step.
   */
  public function currentApproval(): ?SppdApproval
  {
    return $this->approvals()
      ->where('status', 'pending')
      ->orderBy('step_order')
      ->first();
  }

  /**
   * Calculate the trip duration in days.
   */
  public function durationInDays(): int
  {
    return $this->start_date->diffInDays($this->end_date) + 1;
  }

  /**
   * Accessor: $sppd->duration_days
   */
  public function getDurationDaysAttribute(): int
  {
    return $this->durationInDays();
  }
}
