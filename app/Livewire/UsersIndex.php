<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithToast;
use App\Models\Department;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class UsersIndex extends Component
{
  use WithPagination;
  use InteractsWithToast;

  #[Url(keep: true)]
  public string $search = '';

  #[Url(keep: true)]
  public string $type = '';

  #[Url(keep: true)]
  public string $department_id = '';

  #[Url(keep: true)]
  public string $partai = '';

  public function isDprd(): bool
  {
    return $this->type === 'dprd';
  }

  public function searchPlaceholder(): string
  {
    return $this->isDprd()
      ? 'Cari nama, jabatan, role, atau partai...'
      : 'Cari nama, jabatan, atau role...';
  }

  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  public function updatedDepartmentId(): void
  {
    $this->resetPage();
  }

  public function updatedPartai(): void
  {
    $this->resetPage();
  }

  public function resetFilters(): void
  {
    $this->search = '';
    $this->department_id = '';
    $this->partai = '';
    $this->resetPage();
  }

  public function toggleActive(int $id): void
  {
    $user = User::findOrFail($id);
    $user->update(['is_active' => ! $user->is_active]);
    $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

    $this->toastSuccess("Pegawai {$user->name} berhasil {$status}.");
  }

  public function deleteUser(int $id): void
  {
    $user = User::findOrFail($id);
    $name = $user->name;

    try {
      $user->delete();
    } catch (\Throwable $e) {
      $this->toastError("Pegawai {$name} tidak dapat dihapus karena masih terkait dengan data lain.");
      return;
    }

    $this->toastSuccess("Pegawai {$name} berhasil dihapus.");
  }

  private function getHierarchicalDepartments(): array
  {
    $user = auth()->user();

    if ($user->hasRole('super_admin')) {
      $roots = Department::whereNull('parent_id')->orderBy('name')->get();
    } else {
      // Admin OPD bisa melihat OPD induknya dan semua sub-department di bawahnya
      $dept  = $user->department;
      $roots = $dept ? Department::where('id', $dept->id)->get() : collect();
    }

    $list = [];
    foreach ($roots as $root) {
      $this->flattenDepartment($root, 0, $list);
    }

    return $list;
  }

  private function flattenDepartment($dept, $level, &$list): void
  {
    $dept->display_name = str_repeat('— ', $level) . $dept->name;
    $list[]             = $dept;

    foreach ($dept->children()->orderBy('name')->get() as $child) {
      $this->flattenDepartment($child, $level + 1, $list);
    }
  }

  public function render()
  {
    $query = User::with(['department', 'rank', 'position', 'roles'])
      // Akun super_admin tidak ditampilkan di daftar pegawai.
      ->whereDoesntHave('roles', fn($r) => $r->where('name', 'super_admin'));

    // Filter berdasarkan instansi user jika bukan super admin
    // Termasuk semua pegawai di sub-department (bidang/subbidang)
    if (! auth()->user()->hasRole('super_admin')) {
      $dept = auth()->user()->department;
      if ($dept) {
        $relatedIds = $dept->getAllRelatedIds();
        $query->whereIn('department_id', $relatedIds);
      } else {
        $query->where('department_id', auth()->user()->department_id);
      }
    }

    if ($this->isDprd()) {
      $query->where(function ($q) {
        $q->whereHas('roles', fn($r) => $r->where('name', 'anggota_dprd'))
          ->orWhere('employee_type', 'dprd')
          ->orWhereNotNull('dprd_jabatan');
      });
    } else {
      $query->where(function ($q) {
        $q->whereDoesntHave('roles', fn($r) => $r->where('name', 'anggota_dprd'))
          ->where('employee_type', '!=', 'dprd')
          ->whereNull('dprd_jabatan');
      });
    }

    if ($this->search !== '') {
      $s = $this->search;
      $isDprd = $this->isDprd();
      $query->where(function ($q) use ($s, $isDprd) {
        // Cari nama & role (cocokkan label maupun nama teknis role)
        $q->where('name', 'like', "%{$s}%")
          ->orWhereHas('roles', fn($r) => $r->where('label', 'like', "%{$s}%")
            ->orWhere('name', 'like', "%{$s}%"));

        if ($isDprd) {
          // Jabatan DPRD disimpan di dprd_jabatan; sertakan pencarian partai
          $q->orWhere('dprd_jabatan', 'like', "%{$s}%")
            ->orWhere('partai', 'like', "%{$s}%");
        } else {
          // Jabatan pegawai biasa berasal dari relasi position
          $q->orWhereHas('position', fn($p) => $p->where('name', 'like', "%{$s}%"));
        }
      });
    }

    if ($this->department_id !== '' && auth()->user()->hasRole('super_admin')) {
      $query->where('department_id', $this->department_id);
    }

    // Untuk daftar DPRD, filter instansi diganti filter partai/fraksi.
    if ($this->isDprd() && $this->partai !== '') {
      $query->where('partai', $this->partai);
    }

    // Order by department from root to descendants, then by name
    $sortedDeptIds = Department::whereNull('parent_id')
      ->with('children')
      ->get()
      ->flatMap(function ($dept) {
        return $dept->getAllRelatedIds();
      })
      ->filter()
      ->unique()
      ->values()
      ->toArray();

    if (! empty($sortedDeptIds)) {
      // Cast ke integer secara defensif sebelum di-interpolasi ke raw SQL (cegah SQL injection).
      $idsString = implode(',', array_map('intval', $sortedDeptIds));
      $query->orderByRaw("FIELD(department_id, {$idsString}) = 0")
        ->orderByRaw("FIELD(department_id, {$idsString})");
    }

    $users = $query->orderBy('name')->paginate(20)->onEachSide(1);

    // Build a depth map for department indentation — avoids N+1 by using in-memory lookup
    $allDepts     = Department::all()->keyBy('id');
    $deptDepthMap = $allDepts->mapWithKeys(function ($dept) use ($allDepts) {
      $depth   = 0;
      $current = $dept;
      while ($current->parent_id && $allDepts->has($current->parent_id)) {
        $depth++;
        $current = $allDepts->get($current->parent_id);
      }

      return [$dept->id => $depth];
    });

    $departments = $this->getHierarchicalDepartments();

    // Daftar partai/fraksi unik untuk filter searchable select pada daftar DPRD.
    $partaiList = $this->isDprd()
      ? User::whereNotNull('partai')->where('partai', '!=', '')->distinct()->orderBy('partai')->pluck('partai')
      : collect();

    return view('livewire.users-index', compact('users', 'departments', 'deptDepthMap', 'partaiList'))
      ->title('Data Pegawai');
  }
}
