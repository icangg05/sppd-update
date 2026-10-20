<?php

namespace App\Livewire;

use App\Enums\DepartmentType;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class DepartmentIndex extends Component
{
  use InteractsWithToast;

  /** Akun administratif bukan kandidat pimpinan unit. */
  private const ADMIN_ROLES = ['admin_opd', 'super_admin'];

  /** Role yang tidak dihitung sebagai jabatan pimpinan saat mengurutkan kandidat. */
  private const NON_LEADER_ROLES = ['staf', 'anggota_dprd'];

  // Pencarian & filter tipe disaring di sisi klien (Alpine) — tetap disimpan di URL.
  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $type = '';

  // Konfirmasi hapus via modal (hanya bisa ditutup lewat tombol).
  public bool $showDeleteModal = false;
  public ?int $deletingId = null;
  public ?string $deletingName = null;

  // Pemilih pimpinan unit (head_id).
  public bool $showHeadModal = false;
  public ?int $headDeptId = null;
  public string $headSearch = '';

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

    $department   = Department::findOrFail($this->deletingId);
    $user         = auth()->user();
    $isSuperAdmin = $user->hasRole('super_admin');

    // Proteksi: Tidak boleh menghapus instansi yang masih memiliki sub-unit
    if ($department->children()->count() > 0) {
      $this->toastError('Gagal: Instansi ini masih memiliki sub-unit (Bidang/Seksi) di bawahnya.');
      return;
    }

    // Proteksi untuk Admin OPD
    if (! $isSuperAdmin) {
      // Tidak boleh menghapus instansi induk (top-level)
      if ($department->parent_id === null) {
        $this->toastError('Anda tidak memiliki otoritas untuk menghapus Instansi Utama (OPD).');
        return;
      }

      // Pastikan unit yang dihapus berada di bawah naungan OPD-nya
      $isOwned = false;
      $check   = $department;
      while ($check->parent_id !== null) {
        if ($check->parent_id == $user->department_id) {
          $isOwned = true;
          break;
        }
        $check = $check->parent;
      }

      if (! $isOwned) {
        $this->toastError('Anda tidak memiliki otoritas untuk menghapus unit di luar organisasi Anda.');
        return;
      }
    }

    $name = $department->name;
    $department->delete();

    $this->deletingId   = null;
    $this->deletingName = null;

    $this->refreshTree();
    $this->toastSuccess("Instansi/OPD {$name} berhasil dihapus.");
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

  public function openHeadPicker(int $id): void
  {
    $dept = Department::find($id);
    if (! $dept || ! $this->canManage($dept)) {
      $this->toastError('Anda tidak memiliki akses ke unit ini.');
      return;
    }

    if ($this->isTopUnit($dept)) {
      $this->toastError('Pimpinan tidak diatur untuk instansi induk.');
      return;
    }

    $this->headDeptId    = $dept->id;
    $this->headSearch    = '';
    $this->showHeadModal = true;
  }

  public function closeHeadModal(): void
  {
    $this->showHeadModal = false;
    $this->headDeptId    = null;
    $this->headSearch    = '';
  }

  /**
   * Tetapkan (atau kosongkan, bila null) pimpinan unit yang sedang dibuka.
   * Pimpinan ditempatkan di unit yang dipimpinnya: unit kerja pegawai ikut
   * pindah ke sini, dan posisi pimpinannya di unit lain dikosongkan
   * (satu orang memimpin satu unit). Mengosongkan pimpinan tidak memindah pegawai.
   */
  public function setHead(?int $userId = null): void
  {
    $dept = $this->headDeptId ? Department::find($this->headDeptId) : null;
    if (! $dept || ! $this->canManage($dept) || $this->isTopUnit($dept)) {
      $this->closeHeadModal();
      $this->toastError('Anda tidak memiliki akses untuk mengatur pimpinan unit ini.');
      return;
    }

    if ($userId && ! $this->headCandidateQuery($dept)->whereKey($userId)->exists()) {
      $this->toastError('Pimpinan harus pegawai aktif di lingkup unit ini.');
      return;
    }

    $head = $userId ? User::with(['roles', 'position'])->find($userId) : null;
    if ($head && ($error = $this->placementError($head, $dept))) {
      $this->toastError($error);
      return;
    }

    $moved = $head && (int) $head->department_id !== (int) $dept->id;

    DB::transaction(function () use ($dept, $head, $moved) {
      if ($head) {
        // Lewat model agar tercatat di log aktivitas.
        Department::where('head_id', $head->id)->whereKeyNot($dept->id)->get()->each->update(['head_id' => null]);
        if ($moved) {
          $head->update(['department_id' => $dept->id]);
        }
      }
      $dept->update(['head_id' => $head?->id]);
    });

    $this->closeHeadModal();
    $this->refreshTree($dept->id);
    $this->toastSuccess(match (true) {
      ! $head => "Pimpinan {$dept->name} dikosongkan.",
      $moved  => "{$head->name} ditetapkan sebagai pimpinan dan ditempatkan di {$dept->name}.",
      default => "{$head->name} ditetapkan sebagai pimpinan {$dept->name}.",
    });
  }

  /**
   * Alasan pegawai tidak bisa ditempatkan di unit ini, atau null bila boleh.
   * Sama dengan form pegawai: jabatan/role tunggal per unit tidak boleh bentrok
   * dengan pemegang aktif lain di unit tujuan.
   */
  private function placementError(User $user, Department $dept): ?string
  {
    if ((int) $user->department_id === (int) $dept->id) {
      return null;
    }

    if ($holder = $user->position?->conflictingHolder($dept->id, $user->id)) {
      return "Jabatan {$user->position->name} di {$dept->name} sudah dijabat {$holder->name}. Nonaktifkan pejabat lama terlebih dahulu.";
    }

    foreach ($user->roles as $role) {
      if ($holder = User::roleConflictHolder($role->name, $dept->id, $user->id)) {
        $label = $role->label ?? $role->name;

        return "Role {$label} di {$dept->name} sudah dipegang {$holder->name}. Nonaktifkan pejabat lama terlebih dahulu.";
      }
    }

    return null;
  }

  /**
   * Unit paling atas di pohon — instansi induk, atau instansi admin OPD sendiri —
   * tidak memakai pemilih pimpinan; pimpinan diatur per bidang/seksi di bawahnya.
   */
  private function isTopUnit(Department $dept): bool
  {
    $user = auth()->user();

    return $dept->parent_id === null
      || (! $user->hasRole('super_admin') && (int) $dept->id === (int) $user->department_id);
  }

  /**
   * Pegawai aktif di lingkup data unit (instansi induk + bidang/seksinya): pegawai
   * bidang sering masih terdaftar di instansi induk. Admin OPD dibatasi lingkupnya
   * sendiri; akun administratif bukan kandidat.
   */
  private function headCandidateQuery(Department $dept)
  {
    $deptIds = $dept->getScopeRootDepartment()->getScopedRelatedIds();
    if (($manageable = $this->manageableIds()) !== null) {
      $deptIds = $deptIds->intersect($manageable);
    }

    return User::query()
      ->where('is_active', true)
      ->whereIn('department_id', $deptIds)
      ->whereDoesntHave('roles', fn($q) => $q->whereIn('roles.name', self::ADMIN_ROLES));
  }

  /**
   * Kandidat pimpinan (pencarian server-side, dibatasi). Urutan: pegawai yang
   * terdaftar di unit ini, lalu pemegang role pimpinan, lalu menurut nama.
   *
   * @return array{0: Collection, 1: bool}
   */
  private function headCandidates(Department $dept): array
  {
    $limit  = 25;
    $term   = trim($this->headSearch);
    $tables = config('permission.table_names');

    $holdsLeaderRole = DB::table($tables['model_has_roles'])
      ->join($tables['roles'], "{$tables['roles']}.id", '=', "{$tables['model_has_roles']}.role_id")
      ->whereColumn("{$tables['model_has_roles']}.model_id", 'users.id')
      ->where("{$tables['model_has_roles']}.model_type", (new User)->getMorphClass())
      ->whereNotIn("{$tables['roles']}.name", self::NON_LEADER_ROLES)
      ->selectRaw('count(*)');

    $candidates = $this->headCandidateQuery($dept)
      ->with(['roles:id,name,label', 'position:id,name', 'department:id,name'])
      ->when($term !== '', fn($q) => $q->where(fn($w) => $w
        ->where('users.name', 'like', "%{$term}%")
        ->orWhere('users.nip', 'like', "%{$term}%")))
      ->orderByRaw('users.department_id = ? desc', [$dept->id])
      ->orderByDesc($holdsLeaderRole)
      ->orderBy('users.name')
      ->limit($limit + 1)
      ->get();

    return [$candidates->take($limit), $candidates->count() > $limit];
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
      ->with('head:id,name')
      ->treeOrder()
      ->get(['id', 'parent_id', 'head_id', 'name', 'code', 'type', 'letterhead', 'sort_order']);

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
        'head'      => $dept->head?->name,
        'isOwn'     => $isOwn,
        'canDrag'   => $isSuperAdmin || ! $isOwn,
        'canDelete' => $isSuperAdmin || ($dept->parent_id !== null && ! $isOwn),
        // Sama dengan isTopUnit(): baris paling atas tidak punya pemilih pimpinan.
        'canHead'   => $depth > 0,
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

    // Data modal pimpinan.
    $headDept = $this->showHeadModal && $this->headDeptId ? Department::with('head:id,name,nip')->find($this->headDeptId) : null;
    [$headCandidates, $headHasMore] = $headDept ? $this->headCandidates($headDept) : [collect(), false];
    // Kandidat yang sedang memimpin unit lain (akan dilepas bila dipilih di sini).
    $headsElsewhere = $headDept
      ? Department::whereIn('head_id', $headCandidates->pluck('id'))->whereKeyNot($headDept->id)->pluck('name', 'head_id')
      : collect();

    return view('livewire.departments.index', [
      'rows'           => $rows,
      'types'          => DepartmentType::cases(),
      'isSuperAdmin'   => $isSuperAdmin,
      'headDept'       => $headDept,
      'headCandidates' => $headCandidates,
      'headHasMore'    => $headHasMore,
      'headsElsewhere' => $headsElsewhere,
      'nonLeaderRoles' => self::NON_LEADER_ROLES,
    ])->title($isSuperAdmin ? 'Data Instansi' : 'Kelola Unit Kerja');
  }
}
