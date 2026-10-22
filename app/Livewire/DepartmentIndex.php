<?php

namespace App\Livewire;

use App\Enums\DepartmentType;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class DepartmentIndex extends Component
{
  use InteractsWithToast;

  // Pencarian & filter tipe disaring di sisi klien (Alpine) — tetap disimpan di URL.
  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $type = '';

  // Konfirmasi hapus via modal (hanya bisa ditutup lewat tombol).
  public bool $showDeleteModal = false;
  public ?int $deletingId = null;
  public ?string $deletingName = null;

  /** Susunan unit kerja hanya diatur Super Admin; Admin OPD langsung ke profil instansinya. */
  public function mount(): void
  {
    $user = auth()->user();
    if (! $user->hasRole('super_admin')) {
      abort_unless($user->department_id, 403, 'Anda belum memiliki instansi terkait.');
      $this->redirectRoute('master.departments.show', $user->department_id, navigate: true);
    }
  }

  /** Aksi susun/hapus juga ditolak bila dipanggil langsung oleh selain Super Admin. */
  public function hydrate(): void
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403);
  }

  public function resetFilters(): void
  {
    $this->search = '';
    $this->type   = '';
  }

  /**
   * Id unit yang boleh dikelola: null = semua (super admin); admin OPD hanya
   * zona datanya (instansinya + turunan yang berbagi data).
   */
  private function manageableIds(): ?Collection
  {
    $user = auth()->user();
    if ($user->hasRole('super_admin')) {
      return null;
    }

    return $user->department
      ? $user->department->getScopedRelatedIds()
      : collect([$user->department_id]);
  }

  private function canManage(Department $dept): bool
  {
    $ids = $this->manageableIds();

    return $ids === null || $ids->contains($dept->id);
  }

  public function confirmDelete(int $id): void
  {
    $department = Department::find($id);
    if (! $department) {
      return;
    }

    $this->deletingId      = $department->id;
    $this->deletingName    = $department->name;
    $this->showDeleteModal = true;
  }

  public function closeDeleteModal(): void
  {
    $this->showDeleteModal = false;
    $this->deletingId      = null;
    $this->deletingName    = null;
  }

  public function delete(): void
  {
    // Tutup modal lebih dulu agar toast hasil terlihat jelas.
    $this->showDeleteModal = false;

    if (! $this->deletingId) {
      return;
    }

    $department = Department::findOrFail($this->deletingId);
    if ($error = $department->deletionError()) {
      $this->toastError("Gagal: {$error}");
      return;
    }

    $name = $department->name;
    $moved = $department->deleteIntoParent();

    $this->deletingId   = null;
    $this->deletingName = null;

    $this->refreshTree();
    $this->toastSuccess("Unit {$name} berhasil dihapus." . ($moved ? " {$moved} pegawai dipindah ke unit induknya." : ''));
  }

  /**
   * Simpan susunan struktur hasil seret-lepas sekaligus. $nodes berisi
   * {id, parent, order} untuk setiap baris pohon di layar; hanya yang berbeda
   * dari database yang ditulis. Seluruh susunan divalidasi dulu (lingkup,
   * siklus, nama ganda) — tidak ada yang tersimpan bila satu saja tidak sah.
   * Mengembalikan true bila tersimpan (dipakai klien untuk menyorot baris).
   */
  public function saveStructure(array $nodes): bool
  {
    $manageable = $this->manageableIds();
    // Instansi admin OPD sendiri = puncak lingkupnya, tidak ikut disusun.
    $ownId = $manageable === null ? null : (int) auth()->user()->department_id;

    $all     = Department::get(['id', 'parent_id', 'name', 'sort_order'])->keyBy('id');
    $parents = $all->map(fn($d) => $d->parent_id)->all();
    $orders  = [];
    $moved   = [];

    foreach ($nodes as $node) {
      $id     = (int) ($node['id'] ?? 0);
      $parent = isset($node['parent']) ? (int) $node['parent'] : null;

      if ($id === $ownId) {
        continue;
      }
      if (! $all->has($id) || ($parent !== null && ! $all->has($parent))) {
        $this->toastError('Data unit kerja sudah berubah. Muat ulang halaman lalu susun kembali.');
        return false;
      }
      if ($manageable !== null && (! $manageable->contains($id) || $parent === null || ! $manageable->contains($parent))) {
        $this->toastError('Unit hanya dapat disusun di dalam lingkup instansi Anda.');
        return false;
      }

      if ($parents[$id] !== $parent) {
        $moved[$id] = true;
      }
      $parents[$id] = $parent;
      $orders[$id]  = max(0, (int) ($node['order'] ?? 0));
    }

    if ($error = $this->structureError(array_keys($moved), $parents, $all)) {
      $this->toastError($error);
      return false;
    }

    $reordered = collect($orders)->filter(fn($order, $id) => (int) $all[$id]->sort_order !== $order);
    if (! $moved && $reordered->isEmpty()) {
      $this->refreshTree();
      $this->toastInfo('Tidak ada perubahan struktur.');

      return false;
    }

    DB::transaction(function () use ($moved, $parents, $reordered) {
      // Yang paling dangkal dulu, agar level anak dihitung dari level induk yang sudah final.
      $depth = function (int $id) use ($parents): int {
        $n = 0;
        for ($p = $parents[$id]; $p !== null; $p = $parents[$p] ?? null) {
          $n++;
        }

        return $n;
      };

      foreach (collect(array_keys($moved))->sortBy($depth) as $id) {
        $parentId = $parents[$id];
        $dept     = Department::find($id);
        // Lewat model agar perpindahan induk tercatat di log aktivitas.
        $dept->update([
          'parent_id' => $parentId,
          'level'     => $parentId ? (Department::whereKey($parentId)->value('level') ?? 1) + 1 : 1,
        ]);
        $this->syncDescendantLevels($dept);
      }

      // Urutan antar-saudara: kolom tampilan, tidak perlu di-log.
      foreach ($reordered as $id => $order) {
        Department::whereKey($id)->update(['sort_order' => $order]);
      }
    });

    $this->refreshTree();
    $this->toastSuccess($moved
      ? 'Struktur unit kerja disimpan, ' . count($moved) . ' unit dipindahkan.'
      : 'Urutan unit kerja disimpan.');

    return true;
  }

  /**
   * Alasan susunan ditolak, atau null bila sah: unit tidak boleh masuk ke
   * sub-unitnya sendiri, dan nama harus unik di bawah induk yang sama
   * (aturan yang sama dengan form unit kerja).
   *
   * @param  array<int, ?int>  $parents  induk final per id
   */
  private function structureError(array $movedIds, array $parents, Collection $all): ?string
  {
    foreach ($movedIds as $id) {
      $seen = [];
      for ($p = $parents[$id]; $p !== null; $p = $parents[$p] ?? null) {
        if ($p === $id || isset($seen[$p])) {
          return "{$all[$id]->name} tidak dapat dipindahkan ke dalam sub-unitnya sendiri.";
        }
        $seen[$p] = true;
      }

      $name = mb_strtolower(trim($all[$id]->name));
      foreach ($parents as $other => $p) {
        if ($other !== $id && $p === $parents[$id] && mb_strtolower(trim($all[$other]->name)) === $name) {
          $tingkat = $p ? "di bawah {$all[$p]->name}" : 'pada tingkat instansi induk';

          return "Sudah ada unit bernama \"{$all[$id]->name}\" {$tingkat}.";
        }
      }
    }

    return null;
  }

  /** Level turunan mengikuti posisi baru (kolom turunan, tidak perlu di-log). */
  private function syncDescendantLevels(Department $dept): void
  {
    $level = $dept->level;
    $ids   = [$dept->id];

    while ($ids = Department::whereIn('parent_id', $ids)->pluck('id')->all()) {
      $level++;
      Department::whereIn('id', $ids)->update(['level' => $level]);
    }
  }

  /** Kirim ulang data pohon ke Alpine (daftar dirender di klien, wire:ignore). */
  private function refreshTree(?int $focusId = null): void
  {
    $this->dispatch('department-tree-updated', rows: $this->treeRows(), focus: $focusId);
  }

  /**
   * Baris pohon unit kerja (pre-order) untuk tampilan berjenjang di Alpine.
   * Satu query + peta induk→anak di memori (tanpa N+1).
   */
  private function treeRows(): array
  {
    $user         = auth()->user();
    $isSuperAdmin = $user->hasRole('super_admin');
    $scopedIds    = $this->manageableIds();

    if (! $isSuperAdmin && ! $user->department_id) {
      abort(403, 'Anda belum memiliki instansi terkait.');
    }

    $all = Department::withCount('users')
      ->treeOrder()
      ->get(['id', 'parent_id', 'name', 'code', 'type', 'letterhead', 'sort_order']);

    $byId     = $all->keyBy('id');
    $byParent = $all->groupBy(fn($d) => $d->parent_id ?? 0);

    // Unit dianggap "sudah punya kop" bila dirinya atau salah satu leluhurnya
    // memiliki kop (sub-unit mewarisi kop induk).
    $kopMemo = [];
    $hasKop  = function (int $id) use (&$hasKop, &$kopMemo, $byId): bool {
      if (! array_key_exists($id, $kopMemo)) {
        $node = $byId->get($id);
        $kopMemo[$id] = $node && (! empty($node->letterhead) || ($node->parent_id && $hasKop($node->parent_id)));
      }

      return $kopMemo[$id];
    };

    $roots = $isSuperAdmin
      ? $byParent->get(0, collect())
      : $all->where('id', $user->department_id)->values();

    $rows = [];
    $walk = function (Department $dept, int $depth) use (&$walk, &$rows, $byParent, $scopedIds, $hasKop, $isSuperAdmin, $user) {
      // Admin OPD: unit di luar zona datanya tidak ditampilkan.
      $children = $byParent->get($dept->id, collect())
        ->filter(fn($c) => $scopedIds === null || $scopedIds->contains($c->id))
        ->values();
      $isOwn = ! $isSuperAdmin && (int) $dept->id === (int) $user->department_id;

      $rows[] = [
        'id'        => $dept->id,
        'parent'    => $depth === 0 ? null : $dept->parent_id,
        'depth'     => $depth,
        'name'      => $dept->name,
        'code'      => $dept->code,
        'type'      => $dept->type?->value,
        'typeLabel' => $dept->type?->label(),
        'typeBadge' => $dept->type?->badgeClasses(),
        'users'     => $dept->users_count,
        'children'  => $children->count(),
        'hasKop'    => $hasKop($dept->id),
        'isOwn'     => $isOwn,
        'canDrag'   => $isSuperAdmin || ! $isOwn,
        'canDelete' => $isSuperAdmin || ($dept->parent_id !== null && ! $isOwn),
        'showUrl'   => route('master.departments.show', $dept->id),
        'editUrl'   => route('master.departments.edit', $dept->id),
      ];

      foreach ($children as $child) {
        $walk($child, $depth + 1);
      }
    };

    foreach ($roots as $root) {
      $walk($root, 0);
    }

    return $rows;
  }

  public function render()
  {
    $isSuperAdmin = auth()->user()->hasRole('super_admin');
    $rows = $this->treeRows();

    return view('livewire.departments.index', [
      'rows'           => $rows,
      'types'          => DepartmentType::cases(),
      'isSuperAdmin'   => $isSuperAdmin,
    ])->title($isSuperAdmin ? 'Data Instansi' : 'Kelola Unit Kerja');
  }
}
