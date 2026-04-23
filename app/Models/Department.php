<?php

namespace App\Models;

use App\Enums\DepartmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
  protected $fillable = [
    'parent_id',
    'head_id',
    'name',
    'code',
    'letterhead',
    'type',
    'level',
  ];

  protected function casts(): array
  {
    return [
      'type' => DepartmentType::class,
      'level' => 'integer',
    ];
  }

  // ──────────────────────────────────────────────
  // Relationships
  // ──────────────────────────────────────────────

  public function parent(): BelongsTo
  {
    return $this->belongsTo(Department::class, 'parent_id');
  }

  public function head(): BelongsTo
  {
    return $this->belongsTo(User::class, 'head_id');
  }

  public function children(): HasMany
  {
    return $this->hasMany(Department::class, 'parent_id');
  }

  /**
   * Recursive children for building full tree.
   */
  public function allChildren(): HasMany
  {
    return $this->children()->with('allChildren');
  }

  public function users(): HasMany
  {
    return $this->hasMany(User::class);
  }

  public function budgets(): HasMany
  {
    return $this->hasMany(Budget::class);
  }

  public function signatories(): HasMany
  {
    return $this->hasMany(DocumentSignatory::class);
  }

  /**
   * Get the letterhead for this department.
   * If not set, recursively search up the hierarchy to find a parent's letterhead.
   */
  public function getInheritedLetterhead(): ?string
  {
    if (!empty($this->letterhead)) {
      return $this->letterhead;
    }

    if ($this->parent) {
      return $this->parent->getInheritedLetterhead();
    }

    return null;
  }
}
