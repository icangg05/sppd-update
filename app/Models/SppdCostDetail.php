<?php

namespace App\Models;

use App\Enums\CostCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppdCostDetail extends Model
{
  protected $fillable = [
    'sppd_request_id',
    'user_id',
    'cost_category',
    'description',
    'airline_name',
    'ticket_number',
    'unit_cost',
    'quantity',
    'total',
    'receipt_photo',
  ];

  protected function casts(): array
  {
    return [
      'cost_category' => CostCategory::class,
      'unit_cost' => 'integer',
      'quantity' => 'integer',
      'total' => 'integer',
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
