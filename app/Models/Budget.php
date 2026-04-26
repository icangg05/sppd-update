<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
  protected $guarded = ['id'];

  protected $appends = ['realization', 'balance', 'realization_percentage'];

  protected function casts(): array
  {
    return [
      'year'         => 'integer',
      'total_amount' => 'decimal:2',
    ];
  }

  public function getRealizationAttribute(): float
  {
    $requestIdQuery = $this->sppdRequests()
      ->whereIn('status', ['approved', 'completed'])
      ->select('id');

    $totalCostDetails = SppdCostDetail::whereIn('sppd_request_id', $requestIdQuery)->sum('total');
    $totalActualExpenses = SppdActualExpense::whereIn('sppd_request_id', $requestIdQuery)->sum('amount');

    return (float) ($totalCostDetails + $totalActualExpenses);
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
