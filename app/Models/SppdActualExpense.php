<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppdActualExpense extends Model
{
  protected $fillable = [
    'sppd_request_id',
    'user_id',
    'description',
    'amount',
    'receipt_file',
  ];

  protected function casts(): array
  {
    return [
      'amount' => 'decimal:2',
    ];
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
