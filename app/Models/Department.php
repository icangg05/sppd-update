<?php

namespace App\Models;

use App\Enums\DepartmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Department extends Model
{
  use LogsActivity;

  protected $fillable = [
    'parent_id',
    'head_id',
    'name',
    'code',
    'letterhead',
    'letterhead_second',
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

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logOnly(['name', 'code', 'parent_id', 'head_id', 'type', 'level'])
      ->logOnlyDirty()
      ->dontLogEmptyChanges()
      ->setDescriptionForEvent(fn (string $event) => $this->describeLogEvent($event));
  }

  protected function describeLogEvent(string $event): string
  {
    $label = $this->name ? "Unit Kerja {$this->name}" : "Unit Kerja #{$this->getKey()}";

    return match ($event) {
      'created' => "{$label} dibuat",
      'updated' => "{$label} diperbarui",
      'deleted' => "{$label} dihapus",
      default   => "{$label} {$event}",
    };
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

  /**
   * Get all descendant department IDs (children, grandchildren, etc.)
   *
   * @return \Illuminate\Support\Collection<int, int>
   */
  public function getDescendantIds(): \Illuminate\Support\Collection
  {
    $ids = collect();
    foreach ($this->children as $child) {
      $ids->push($child->id);
      $ids = $ids->merge($child->getDescendantIds());
    }

    return $ids;
  }

  /**
   * Get this department's ID plus all descendant IDs.
   *
   * @return \Illuminate\Support\Collection<int, int>
   */
  public function getAllRelatedIds(): \Illuminate\Support\Collection
  {
    return collect([$this->id])->merge($this->getDescendantIds());
  }

  /**
   * Get the root OPD for this department (walk up to topmost parent).
   */
  public function getRootDepartment(): self
  {
    $dept = $this;
    while ($dept->parent_id !== null) {
      $dept = $dept->parent;
    }

    return $dept;
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

  /**
   * Get the second letterhead for this department.
   * If not set, recursively search up the hierarchy to find a parent's second letterhead.
   */
  public function getInheritedLetterheadSecond(): ?string
  {
    if (!empty($this->letterhead_second)) {
      return $this->letterhead_second;
    }

    if ($this->parent) {
      return $this->parent->getInheritedLetterheadSecond();
    }

    return null;
  }
}
