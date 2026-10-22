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
    'setuju_bayar_user_id',
    'setuju_bayar_label',
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
      ->logOnly(['name', 'code', 'parent_id', 'setuju_bayar_user_id', 'setuju_bayar_label', 'type', 'level', 'can_view_children_data'])
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

  public function setujuBayarUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'setuju_bayar_user_id');
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
          return "Role {$role->label} {$user->name} bentrok dengan {$holder->name} di unit induk. Sesuaikan role-nya terlebih dahulu.";
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
   * Penandatangan "Setuju Bayar" pada dokumen cetak (kuitansi, pengeluaran
   * riil, rincian biaya) untuk pegawai di department ini.
   *
   * Resolusi naik dari department ini sampai root zona data (mis. Dinas) —
   * setting yang paling dekat menang. Cukup diatur SEKALI di tingkat Dinas:
   * semua sub-unit di bawahnya (Bidang/Seksi) otomatis mewarisi tanpa perlu
   * diset satu-satu. User tersimpan yang nonaktif dilewati; bila tak ada setting
   * eksplisit, fallback ke user role kepala_opd di root zona. Label di-resolve
   * terpisah agar tetap berlaku walau kolom user kosong (null = label bawaan
   * per dokumen). Zona mandiri (kelurahan/puskesmas) berhenti di dirinya sendiri.
   *
   * @return array{user: ?User, label: ?string, opd: Department}
   */
  public function resolveSetujuBayar(): array
  {
    $scopeRoot = $this->getScopeRootDepartment();

    $user  = null;
    $label = null;
    $dept  = $this;
    while (true) {
      $candidate = $dept->setujuBayarUser;
      if (! $user && $candidate && $candidate->is_active) {
        $user = $candidate;
      }
      if ($label === null && $dept->setuju_bayar_label) {
        $label = $dept->setuju_bayar_label;
      }
      if ($dept->id === $scopeRoot->id || $dept->parent === null) {
        break;
      }
      $dept = $dept->parent;
    }

    if (! $user) {
      $user = $scopeRoot->defaultSetujuBayarUser();
    }

    return ['user' => $user, 'label' => $label, 'opd' => $scopeRoot];
  }

  /** Penandatangan Setuju Bayar bawaan bila belum diatur: Kepala OPD aktif di unit ini. */
  public function defaultSetujuBayarUser(): ?User
  {
    return User::role('kepala_opd')
      ->where('department_id', $this->id)
      ->where('is_active', true)
      ->first();
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
