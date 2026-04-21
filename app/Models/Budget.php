<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
  protected $guarded = ['id'];

  protected $appends = ['realization', 'balance'];

  protected function casts(): array
  {
    return [
      'year'         => 'integer',
      'total_amount' => 'decimal:2',
    ];
  }

  public function getRealizationAttribute(): float
  {
    // Placeholder calculation: sum from associated SPPD requests
    return (float) $this->sppdRequests()
      ->whereIn('status', ['approved', 'completed'])
      ->get()
      ->sum(function ($request) {
        return 0;
      });
  }

  public function getBalanceAttribute(): float
  {
    return (float) $this->total_amount - $this->realization;
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
