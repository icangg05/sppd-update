<?php

namespace App\Models;

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

  public function department(): BelongsTo
  {
    return $this->belongsTo(Department::class);
  }

  public function sppdRequests(): HasMany
  {
    return $this->hasMany(SppdRequest::class);
  }
}
