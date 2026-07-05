<?php

namespace App\Livewire;

use App\Enums\DepartmentType;
use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Department;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class DepartmentIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $type = '';

  // Konfirmasi hapus via modal (hanya bisa ditutup lewat tombol).
  public bool $showDeleteModal = false;
  public ?int $deletingId = null;
  public ?string $deletingName = null;

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function updatedType(): void
  {
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search = '';
    $this->type   = '';
    $this->resetPage();
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

    $this->toastSuccess("Instansi/OPD {$name} berhasil dihapus.");
  }

  private function flattenDepartment($dept, int $level, array &$list, $allowedIds = null): void
  {
    if (! $dept) return;

    // Batasi pada zona data user (unit di luar zona tidak ditampilkan).
    if ($allowedIds !== null && ! $allowedIds->contains($dept->id)) return;

    $dept->tree_level = $level;
    $list[]           = $dept;

    foreach ($dept->children()->withCount(['users', 'budgets', 'children'])->with('head')->orderBy('name')->get() as $child) {
      $this->flattenDepartment($child, $level + 1, $list, $allowedIds);
    }
  }

  private function paginateItems($items, int $perPage): LengthAwarePaginator
  {
    $items = Collection::wrap($items)->values();
    $page  = LengthAwarePaginator::resolveCurrentPage();

    return new LengthAwarePaginator(
      $items->forPage($page, $perPage)->values(),
      $items->count(),
      $perPage,
      $page,
      ['path' => LengthAwarePaginator::resolveCurrentPath()]
    );
  }

  public function render()
  {
    $user         = auth()->user();
    $isSuperAdmin = $user->hasRole('super_admin');

    $query = Department::withCount(['users', 'budgets', 'children'])->with('head');

    $scopedIds = null;
    if (! $isSuperAdmin) {
      // Admin OPD hanya bisa melihat unit dalam zona datanya sendiri
      // (departemennya + turunan yang berbagi data).
      $myDeptId = $user->department_id;
      if (! $myDeptId) abort(403, 'Anda belum memiliki instansi terkait.');

      $scopedIds = $user->department
        ? $user->department->getScopedRelatedIds()
        : collect([$myDeptId]);

      $query->whereIn('id', $scopedIds);
    }

    if ($this->search !== '') {
      $s = $this->search;
      $query->where(function ($q) use ($s) {
        $q->where('name', 'like', "%{$s}%")
          ->orWhere('code', 'like', "%{$s}%");
      });
    }

    if ($this->type !== '' && $isSuperAdmin) {
      $query->where('type', $this->type);
    }

    // Tampilkan secara hierarki tanpa pagination agar tree-nya terlihat
    $list = [];
    if ($isSuperAdmin) {
      // Jika ada pencarian, tampilkan hasil pencarian secara flat
      if ($this->search !== '' || $this->type !== '') {
        $departments = $query->orderBy('name')->get();
      } else {
        $roots = (clone $query)->whereNull('parent_id')->orderBy('name')->get();
        foreach ($roots as $root) {
          $this->flattenDepartment($root, 0, $list);
        }
        $departments = $list;
      }
    } else {
      $root = (clone $query)->find($user->department_id);
      $this->flattenDepartment($root, 0, $list, $scopedIds);
      $departments = $list;
    }

    $departments = $this->paginateItems($departments, 100);

    // Tandai unit yang belum punya kop surat. Kop hanya di-set di tingkat OPD
    // induk, sub-unit mewarisi dari induknya — jadi unit dianggap "sudah punya
    // kop" bila dirinya atau salah satu leluhurnya memiliki kop.
    // Resolusi warisan lewat satu peta (id, parent_id, letterhead) agar bebas N+1.
    $kopLookup = Department::get(['id', 'parent_id', 'letterhead'])->keyBy('id');
    $hasInheritedKop = function ($id) use (&$hasInheritedKop, $kopLookup) {
      $node = $kopLookup->get($id);
      if (! $node) return false;
      if (! empty($node->letterhead)) return true;

      return $node->parent_id ? $hasInheritedKop($node->parent_id) : false;
    };
    foreach ($departments as $dept) {
      $dept->has_kop = $hasInheritedKop($dept->id);
    }

    $types = DepartmentType::cases();

    return view('livewire.departments.index', compact('departments', 'types', 'isSuperAdmin'))
      ->title($isSuperAdmin ? 'Data Instansi' : 'Kelola Unit Kerja');
  }
}
