<?php

namespace App\Http\Controllers;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
  public function index(Request $request)
  {
    $user = auth()->user();
    $isSuperAdmin = $user->hasRole('super_admin');

    $query = Department::withCount(['users', 'budgets', 'children'])->with('head');

    if (!$isSuperAdmin) {
      // Admin OPD hanya bisa melihat unit di bawah departemennya sendiri
      $myDeptId = $user->department_id;
      if (!$myDeptId) abort(403, 'Anda belum memiliki instansi terkait.');
      
      $query->where(function($q) use ($myDeptId) {
          $q->where('id', $myDeptId)
            ->orWhere('parent_id', $myDeptId)
            ->orWhereIn('parent_id', Department::where('parent_id', $myDeptId)->pluck('id'));
      });
    }

    if ($request->filled('search')) {
      $query->where(function($q) use ($request) {
        $q->where('name', 'like', "%{$request->search}%")
          ->orWhere('code', 'like', "%{$request->search}%");
      });
    }

    if ($request->filled('type') && $isSuperAdmin) {
      $query->where('type', $request->type);
    }

    // Untuk Admin OPD, kita tampilkan secara hierarki tanpa pagination agar tree-nya terlihat
    if (!$isSuperAdmin) {
        $root = Department::find($user->department_id);
        $list = [];
        $this->flattenDepartment($root, 0, $list);
        $departments = $list;
    } else {
        $departments = $query->orderBy('name')->paginate(20)->withQueryString();
    }
    
    $types = DepartmentType::cases();

    return view('master.departments.index', compact('departments', 'types', 'isSuperAdmin'));
  }

  public function create()
  {
    $types = DepartmentType::cases();
    $parents = $this->getHierarchicalDepartments();
    
    // Hanya tampilkan user yang belum menjadi pimpinan di instansi manapun
    $assignedHeadIds = Department::whereNotNull('head_id')->pluck('head_id')->toArray();
    $users = User::where('is_active', true)
        ->whereNotIn('id', $assignedHeadIds)
        ->orderBy('name')
        ->get();

    return view('master.departments.create', compact('types', 'parents', 'users'));
  }

  private function getHierarchicalDepartments($excludeId = null)
  {
    $user = auth()->user();
    if ($user->hasRole('super_admin')) {
        $query = Department::whereNull('parent_id');
    } else {
        // Admin OPD hanya bisa memilih departemennya sendiri atau unit di bawahnya sebagai induk
        $query = Department::where('id', $user->department_id);
    }
    
    if ($excludeId) {
        $query->where('id', '!=', $excludeId);
    }
    $roots = $query->orderBy('name')->get();

    $list = [];
    foreach ($roots as $root) {
      $this->flattenDepartment($root, 0, $list, $excludeId);
    }
    
    return $list;
  }

  private function flattenDepartment($dept, $level, &$list, $excludeId = null)
  {
    if ($excludeId && $dept->id == $excludeId) return;

    $dept->display_name = str_repeat('— ', $level) . $dept->name;
    $list[] = $dept;

    foreach ($dept->children()->orderBy('name')->get() as $child) {
      $this->flattenDepartment($child, $level + 1, $list, $excludeId);
    }
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name'      => 'required|string|max:255',
      'code'      => 'nullable|string|max:30|unique:departments,code',
      'type'      => 'required|in:' . implode(',', array_column(DepartmentType::cases(), 'value')),
      'parent_id' => 'nullable|exists:departments,id',
      'head_id'   => 'nullable|exists:users,id',
      'letterhead'=> 'nullable|string',
    ]);

    // Auto level calculation
    if ($validated['parent_id']) {
        $parent = Department::find($validated['parent_id']);
        $validated['level'] = ($parent->level ?? 1) + 1;
        // Inherit type from parent if not set specifically
        if (!$request->filled('type')) {
            $validated['type'] = $parent->type;
        }
    } else {
        $validated['level'] = 1;
    }

    Department::create($validated);

    return redirect()->route('master.departments.index')->with('success', "Instansi {$validated['name']} berhasil ditambahkan.");
  }

  public function show(Department $department)
  {
    // Jika bukan super admin, hanya boleh melihat OPD-nya sendiri dan unit di bawahnya
    if (!auth()->user()->hasRole('super_admin')) {
        $userDeptId = auth()->user()->department_id;
        
        $isDescendant = ($department->id == $userDeptId) || 
                        ($department->parent_id == $userDeptId) ||
                        (Department::where('parent_id', $userDeptId)->where('id', $department->parent_id)->exists());

        if (!$isDescendant) {
            abort(403, 'Anda tidak memiliki akses ke halaman instansi/unit ini.');
        }
    }

    $department->load(['head', 'parent', 'users', 'children']);
    return view('master.departments.show', compact('department'));
  }

  public function edit(Department $department)
  {
    $types = DepartmentType::cases();
    $parents = $this->getHierarchicalDepartments($department->id);
    
    // Ambil pimpinan lain yang sudah menjabat untuk dikecualikan
    $otherHeads = Department::whereNotNull('head_id')
        ->where('id', '!=', $department->id)
        ->pluck('head_id')
        ->toArray();

    // Ambil user dari departemen ini atau departemen induk yang belum jadi pimpinan di tempat lain
    $users = User::where('is_active', true)
        ->whereNotIn('id', $otherHeads)
        ->where(function($q) use ($department) {
            $q->where('department_id', $department->id)
              ->orWhere('department_id', $department->parent_id);
        })
        ->orderBy('name')
        ->get();

    return view('master.departments.edit', compact('department', 'types', 'parents', 'users'));
  }

  public function update(Request $request, Department $department)
  {
    $validated = $request->validate([
      'name'      => 'required|string|max:255',
      'code'      => 'nullable|string|max:30|unique:departments,code,' . $department->id,
      'type'      => 'required|in:' . implode(',', array_column(DepartmentType::cases(), 'value')),
      'parent_id' => 'nullable|exists:departments,id',
      'head_id'   => 'nullable|exists:users,id',
      'letterhead'=> 'nullable|string',
    ]);

    // Re-calculate level on update
    if ($validated['parent_id']) {
        $parent = Department::find($validated['parent_id']);
        $validated['level'] = ($parent->level ?? 1) + 1;
    } else {
        $validated['level'] = 1;
    }

    $department->update($validated);

    // Redirect berdasarkan role
    if (auth()->user()->hasRole('super_admin')) {
        return redirect()->route('master.departments.index')->with('success', "Instansi/OPD {$validated['name']} berhasil diperbarui.");
    }

    return redirect()->route('master.departments.show', $department->id)->with('success', "Profil OPD berhasil diperbarui.");
  }

  public function destroy(Department $department)
  {
    $user = auth()->user();
    $isSuperAdmin = $user->hasRole('super_admin');
    
    // Proteksi: Tidak boleh menghapus instansi yang masih memiliki sub-unit
    if ($department->children()->count() > 0) {
        return back()->with('error', 'Gagal: Instansi ini masih memiliki sub-unit (Bidang/Seksi) di bawahnya.');
    }

    // Proteksi untuk Admin OPD
    if (!$isSuperAdmin) {
        // Tidak boleh menghapus instansi induk (top-level)
        if ($department->parent_id === null) {
            abort(403, 'Anda tidak memiliki otoritas untuk menghapus Instansi Utama (OPD).');
        }
        
        // Pastikan unit yang dihapus adalah miliknya (berada di bawah naungan OPD-nya)
        $isOwned = false;
        $check = $department;
        while ($check->parent_id !== null) {
            if ($check->parent_id == $user->department_id) {
                $isOwned = true;
                break;
            }
            $check = $check->parent; // Mengandalkan relasi 'parent' di model
        }

        if (!$isOwned) {
            abort(403, 'Anda tidak memiliki otoritas untuk menghapus unit di luar organisasi Anda.');
        }
    }

    $name = $department->name;
    $department->delete();

    return redirect()->route('master.departments.index')->with('success', "Instansi/OPD {$name} berhasil dihapus.");
  }
}
