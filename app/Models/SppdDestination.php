<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppdDestination extends Model
{
  protected $fillable = [
    'sppd_request_id',
    'province_id',
    'regency_id',
    'address',
  ];

  public function sppdRequest(): BelongsTo
  {
    return $this->belongsTo(SppdRequest::class);
  }

  public function province(): BelongsTo
  {
    return $this->belongsTo(Province::class);
  }

  public function regency(): BelongsTo
  {
    return $this->belongsTo(Regency::class);
  }
}
