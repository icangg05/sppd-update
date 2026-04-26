<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
  protected $fillable = ['name', 'level'];

  public function users(): HasMany
  {
    return $this->hasMany(User::class);
  }

  public function signatories(): HasMany
  {
    return $this->hasMany(DocumentSignatory::class);
  }
}
