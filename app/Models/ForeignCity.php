<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Kota tujuan perjalanan dinas luar negeri. */
class ForeignCity extends Model
{
  protected $fillable = ['country_id', 'name'];

  public function country(): BelongsTo
  {
    return $this->belongsTo(Country::class);
  }
}
