<?php

namespace App\Models;

use App\Enums\DepartmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Department extends Model
{
  use LogsActivity;

  protected $fillable = [
    'parent_id',
    'name',
    'code',
    'letterhead',
    'letterhead_second',
    'type',
    'level',
    'sort_order',
    'can_view_children_data',
  ];

  protected function casts(): array
  {
    return [
      'type' => DepartmentType::class,
      'level' => 'integer',
      'sort_order' => 'integer',
      'can_view_children_data' => 'boolean',
    ];
  }

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logOnly(['name', 'code', 'parent_id', 'type', 'level', 'can_view_children_data'])
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

  /**
   * Urutan tampilan pohon struktur: sort_order (diatur lewat seret-lepas di
   * halaman unit kerja), lalu nama sebagai pemecah seri.
   */
  public function scopeTreeOrder($query)
  {
    return $query->orderBy('sort_order')->orderBy('name');
  }

  // ──────────────────────────────────────────────
  // Relationships
  // ──────────────────────────────────────────────

  public function parent(): BelongsTo
  {
    return $this->belongsTo(Department::class, 'parent_id');
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
   * Alasan unit ini tidak bisa dihapus, atau null. Pegawainya dipindah ke unit induk
   * (lihat deleteIntoParent), jadi yang ditolak: masih punya sub-unit, anggaran (ikut
   * terhapus bersama unitnya), akun Admin OPD (lingkupnya akan melebar ke unit induk),
   * atau pegawai yang role tunggalnya bentrok dengan pemegang di unit induk.
   */
  public function deletionError(): ?string
  {
    if ($this->children()->exists()) {
      return 'Unit ini masih memiliki sub-unit di bawahnya.';
    }
    if ($this->budgets()->exists()) {
      return 'Unit ini masih memiliki data anggaran (DPA). Pindahkan atau hapus anggarannya terlebih dahulu.';
    }

    $users = $this->users()->with('roles')->get();
    if ($users->isNotEmpty() && ! $this->parent_id) {
      return "Instansi ini masih memiliki {$users->count()} pegawai.";
    }

    $admins = $users->filter(fn (User $u) => $u->hasRole('admin_opd'));
    if ($admins->isNotEmpty()) {
      return 'Unit ini punya akun Admin OPD (' . $admins->pluck('username')->join(', ') . '). Pindahkan atau nonaktifkan akunnya terlebih dahulu.';
    }

    foreach ($users as $user) {
      foreach ($user->roles as $role) {
        if ($holder = User::roleConflictHolder($role->name, $this->parent_id, $user->id)) {
          return "Role {$user->roleLabelFor($role)} {$user->name} bentrok dengan {$holder->name} di unit induk. Sesuaikan role-nya terlebih dahulu.";
        }
      }
    }

    return null;
  }

  /**
   * Hapus unit; pegawainya dipindah ke unit induk agar tidak kehilangan unit kerja.
   * Periksa deletionError lebih dulu. Mengembalikan jumlah pegawai yang dipindah.
   */
  public function deleteIntoParent(): int
  {
    return DB::transaction(function () {
      $users = $this->users()->get();
      // Lewat model agar perpindahan tercatat di log aktivitas pegawai.
      $users->each->update(['department_id' => $this->parent_id]);
      $this->delete();

      return $users->count();
    });
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
   * Hanya Inspektorat yang menginput sendiri nomor surat tugas di form SPPD;
   * instansi lain tidak memakai nomor dokumen.
   */
  public function usesManualNumbering(): bool
  {
    return $this->isInspektorat();
  }

  /**
   * Hanya SPPD Inspektorat yang boleh bertanggal bentrok dengan SPPD lain: pegawainya bisa
   * mendadak diperintah bertugas di tempat lain saat masih dalam perjalanan. Instansi lain
   * tetap ditolak bila tanggalnya bentrok (lihat SppdRequest::clashingForUsers).
   */
  public function allowsClashingTrips(): bool
  {
    return $this->isInspektorat();
  }

  /** Inspektorat dikenali dari namanya. */
  public function isInspektorat(): bool
  {
    return str_contains(strtolower($this->name ?? ''), 'inspektorat');
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
   * ID seluruh unit kerja dalam satu struktur instansi: unit teratas beserta semua turunannya,
   * termasuk zona mandiri di bawahnya (Setda → Asisten → Bagian, Dinkes → Puskesmas,
   * Kecamatan → Kelurahan). Satu query per tingkat.
   *
   * @return \Illuminate\Support\Collection<int, int>
   */
  public function instansiTreeIds(): \Illuminate\Support\Collection
  {
    $frontier = [$this->getRootDepartment()->id];
    $ids = $frontier;

    while ($frontier) {
      $frontier = static::whereIn('parent_id', $frontier)->pluck('id')->map(fn ($id) => (int) $id)->all();
      array_push($ids, ...$frontier);
    }

    return collect($ids);
  }

  /**
   * Root zona data untuk instansi ini: naik ke atas selama induknya masih
   * berbagi data ke bawah (can_view_children_data). Berhenti di bawah induk
   * yang tidak berbagi — instansi ini menjadi root zonanya sendiri.
   */
  public function getScopeRootDepartment(): self
  {
    $dept = $this;
    while (
      $dept->parent !== null
      && ! $dept->type->isIndependentZone()
      && $dept->parent->can_view_children_data
    ) {
      $dept = $dept->parent;
    }

    return $dept;
  }

  /**
   * Sebutan role di zona data unit ini bila berbeda dari label role-nya: di Rumah Sakit,
   * Kepala OPD = "Direktur" dan Sekretaris OPD = "Sekretaris" (DepartmentType::roleTitle).
   */
  public function roleTitle(string $role): ?string
  {
    return $this->getScopeRootDepartment()->type?->roleTitle($role);
  }

  /** Label role untuk ditampilkan di unit ini: sebutan khususnya, atau $label bawaan. */
  public function roleLabel(string $role, ?string $label = null): string
  {
    return $this->roleTitle($role) ?? $label ?? ucwords(str_replace('_', ' ', $role));
  }

  /**
   * ID turunan dalam zona data: kosong bila instansi ini tidak melihat data
   * di bawahnya; rekursif tapi tidak menurun melewati unit yang flag-nya off.
   *
   * @return \Illuminate\Support\Collection<int, int>
   */
  public function getScopedDescendantIds(): \Illuminate\Support\Collection
  {
    if (! $this->can_view_children_data) {
      return collect();
    }

    $ids = collect();
    foreach ($this->children as $child) {
      // Entitas zona mandiri (kelurahan, puskesmas) kelola anggaran/SPPD sendiri —
      // tidak ikut zona induk walau induk berbagi data ke bawah.
      if ($child->type->isIndependentZone()) {
        continue;
      }
      $ids->push($child->id);
      $ids = $ids->merge($child->getScopedDescendantIds());
    }

    return $ids;
  }

  /**
   * ID instansi ini plus seluruh turunan dalam zona data
   * (versi getAllRelatedIds yang menghormati can_view_children_data).
   *
   * @return \Illuminate\Support\Collection<int, int>
   */
  public function getScopedRelatedIds(): \Illuminate\Support\Collection
  {
    return collect([$this->id])->merge($this->getScopedDescendantIds());
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
