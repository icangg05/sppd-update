<?php

namespace App\Models;

use App\Enums\AssignmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDepartmentAssignment extends Model
{
  protected $fillable = [
    'user_id',
    'department_id',
    'assignment_type',
  ];

  protected function casts(): array
  {
    return [
      'assignment_type' => AssignmentType::class,
    ];
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function department(): BelongsTo
  {
    return $this->belongsTo(Department::class);
  }
}
