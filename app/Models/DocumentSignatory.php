<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSignatory extends Model
{
  protected $fillable = [
    'department_id',
    'user_id',
    'position_id',
    'name',
    'title',
    'signature_image',
    'is_active',
  ];

  protected function casts(): array
  {
    return [
      'is_active' => 'boolean',
    ];
  }

  public function department(): BelongsTo
  {
    return $this->belongsTo(Department::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function position(): BelongsTo
  {
    return $this->belongsTo(Position::class);
  }
}
