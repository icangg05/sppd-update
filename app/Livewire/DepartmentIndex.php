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

  public function delete(int $id): void
  {
    $department   = Department::findOrFail($id);
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

    $this->toastSuccess("Instansi/OPD {$name} berhasil dihapus.");
  }

  private function flattenDepartment($dept, int $level, array &$list): void
  {
    if (! $dept) return;

    $dept->tree_level = $level;
    $list[]           = $dept;

    foreach ($dept->children()->withCount(['users', 'budgets', 'children'])->with('head')->orderBy('name')->get() as $child) {
      $this->flattenDepartment($child, $level + 1, $list);
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

    if (! $isSuperAdmin) {
      // Admin OPD hanya bisa melihat unit di bawah departemennya sendiri
      $myDeptId = $user->department_id;
      if (! $myDeptId) abort(403, 'Anda belum memiliki instansi terkait.');

      $query->where(function ($q) use ($myDeptId) {
        $q->where('id', $myDeptId)
          ->orWhere('parent_id', $myDeptId)
          ->orWhereIn('parent_id', Department::where('parent_id', $myDeptId)->pluck('id'));
      });
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
      $this->flattenDepartment($root, 0, $list);
      $departments = $list;
    }

    $departments = $this->paginateItems($departments, 50);

    $types = DepartmentType::cases();

    return view('livewire.departments.index', compact('departments', 'types', 'isSuperAdmin'))
      ->title($isSuperAdmin ? 'Data Instansi' : 'Kelola Unit Kerja');
  }
}
