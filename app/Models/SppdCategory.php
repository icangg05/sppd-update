<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SppdCategory extends Model
{
  protected $fillable = ['name', 'description'];

  public function sppdRequests(): HasMany
  {
    return $this->hasMany(SppdRequest::class, 'category_id');
  }
}
