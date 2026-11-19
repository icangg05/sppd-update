<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Budget extends Model
{
  use LogsActivity;

  protected $guarded = ['id'];

  protected $appends = ['realization', 'balance', 'realization_percentage'];

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logOnly([
        'department_id', 'program', 'activity', 'account_code',
        'description', 'type', 'source', 'year', 'total_amount',
      ])
      ->logOnlyDirty()
      ->dontLogEmptyChanges()
      ->setDescriptionForEvent(fn (string $event) => $this->describeLogEvent($event));
  }

  protected function describeLogEvent(string $event): string
  {
    $label = $this->activity
      ? "Anggaran {$this->activity}"
      : ($this->account_code ? "Anggaran {$this->account_code}" : "Anggaran #{$this->getKey()}");

    return match ($event) {
      'created' => "{$label} dibuat",
      'updated' => "{$label} diperbarui",
      'deleted' => "{$label} dihapus",
      default   => "{$label} {$event}",
    };
  }

  /**
   * Cache realisasi per-instance. Tanpa ini, getRealizationAttribute() dieksekusi 3x
   * (realization + balance + realization_percentage) setiap budget di-serialize → boros query.
   */
  protected ?float $realizationCache = null;

  protected function casts(): array
  {
    return [
      'year'         => 'integer',
      'total_amount' => 'integer',
    ];
  }

  public function getRealizationAttribute(): float
  {
    if ($this->realizationCache !== null) {
      return $this->realizationCache;
    }

    $requestIdQuery = $this->sppdRequests()
      ->whereIn('status', ['approved', 'completed'])
      ->select('id');

    $totalCostDetails = SppdCostDetail::whereIn('sppd_request_id', $requestIdQuery)->sum('total');
    $totalActualExpenses = SppdActualExpense::whereIn('sppd_request_id', $requestIdQuery)->sum('amount');

    return $this->realizationCache = (float) ($totalCostDetails + $totalActualExpenses);
  }

  public function getBalanceAttribute(): float
  {
    return (float) ($this->total_amount - $this->realization);
  }

  public function getRealizationPercentageAttribute(): float
  {
    if ($this->total_amount <= 0) return 0;
    return round(($this->realization / $this->total_amount) * 100, 2);
  }

  /**
   * DPA yang boleh dilihat pengguna: Super Admin semuanya; selain itu seluruh DPA dalam zona data
   * unitnya (sama dengan pilihan DPA di form SPPD), jadi tiap Bagian di Setda melihat DPA Bagian lain.
   */
  public function scopeVisibleTo(Builder $query, User $user): Builder
  {
    if ($user->hasRole('super_admin')) {
      return $query;
    }

    $ids = $user->department?->getScopeRootDepartment()->getScopedRelatedIds() ?? collect([0]);

    return $query->whereIn('department_id', $ids);
  }

  /** Mengubah dan menghapus DPA tetap hanya oleh unit pemiliknya (atau Super Admin). */
  public function isManageableBy(User $user): bool
  {
    return $user->hasRole('super_admin') || $this->department_id === $user->department_id;
  }

  public function department(): BelongsTo
  {
    return $this->belongsTo(Department::class);
  }

  public function sppdRequests(): HasMany
  {
    return $this->hasMany(SppdRequest::class);
  }
}
