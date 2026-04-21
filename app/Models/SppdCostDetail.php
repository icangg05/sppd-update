<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppdCostDetail extends Model
{
  protected $fillable = [
    'sppd_request_id',
    'user_id',
    'description',
    'unit_cost',
    'quantity',
    'total',
  ];

  protected function casts(): array
  {
    return [
      'unit_cost' => 'decimal:2',
      'quantity' => 'integer',
      'total' => 'decimal:2',
    ];
  }

  /**
   * Auto-calculate total before saving.
   */
  protected static function booted(): void
  {
    static::saving(function (SppdCostDetail $model) {
      $model->total = $model->unit_cost * $model->quantity;
    });
  }

  public function sppdRequest(): BelongsTo
  {
    return $this->belongsTo(SppdRequest::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
