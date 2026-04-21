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
    if (!auth()->user()->hasRole('super_admin')) {
        $departmentId = auth()->user()->department_id;
        if ($departmentId) {
            return redirect()->route('master.departments.show', $departmentId);
        }
        return abort(403, 'Anda belum memiliki instansi/OPD terkait.');
    }

    $query = Department::withCount(['users', 'budgets', 'children'])->with('head');

    if ($request->filled('search')) {
      $query->where('name', 'like', "%{$request->search}%")
        ->orWhere('code', 'like', "%{$request->search}%");
    }

    if ($request->filled('type')) {
      $query->where('type', $request->type);
    }

    $departments = $query->orderBy('name')->paginate(20)->withQueryString();
    $types = DepartmentType::cases();

    return view('master.departments.index', compact('departments', 'types'));
  }

  public function create()
  {
    $types = DepartmentType::cases();
    $parents = Department::orderBy('name')->get();
    $users = User::where('is_active', true)->orderBy('name')->get();

    return view('master.departments.create', compact('types', 'parents', 'users'));
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name'      => 'required|string|max:255',
      'code'      => 'required|string|max:30|unique:departments,code',
      'type'      => 'required|in:' . implode(',', array_column(DepartmentType::cases(), 'value')),
      'parent_id' => 'nullable|exists:departments,id',
      'head_id'   => 'nullable|exists:users,id',
      'letterhead'=> 'nullable|string',
    ]);

    Department::create($validated);

    return redirect()->route('master.departments.index')->with('success', "Instansi {$validated['name']} berhasil ditambahkan.");
  }

  public function show(Department $department)
  {
    // Jika bukan super admin, hanya boleh melihat OPD-nya sendiri
    if (!auth()->user()->hasRole('super_admin')) {
        if (auth()->user()->department_id !== $department->id) {
            abort(403, 'Anda tidak memiliki akses ke halaman instansi ini.');
        }
    }

    $department->load(['head', 'parent', 'users', 'children']);
    return view('master.departments.show', compact('department'));
  }

  public function edit(Department $department)
  {
    $types = DepartmentType::cases();
    $parents = Department::where('id', '!=', $department->id)->orderBy('name')->get();
    
    // Hanya ambil user dari departemen ini untuk dijadikan pimpinan
    $users = User::where('department_id', $department->id)->where('is_active', true)->orderBy('name')->get();

    return view('master.departments.edit', compact('department', 'types', 'parents', 'users'));
  }

  public function update(Request $request, Department $department)
  {
    $validated = $request->validate([
      'name'      => 'required|string|max:255',
      'code'      => 'required|string|max:30|unique:departments,code,' . $department->id,
      'type'      => 'required|in:' . implode(',', array_column(DepartmentType::cases(), 'value')),
      'parent_id' => 'nullable|exists:departments,id',
      'head_id'   => 'nullable|exists:users,id',
      'letterhead'=> 'nullable|string',
    ]);

    $department->update($validated);

    // Redirect berdasarkan role
    if (auth()->user()->hasRole('super_admin')) {
        return redirect()->route('master.departments.index')->with('success', "Instansi/OPD {$validated['name']} berhasil diperbarui.");
    }

    return redirect()->route('master.departments.show', $department->id)->with('success', "Profil OPD berhasil diperbarui.");
  }

  public function destroy(Department $department)
  {
    if (!auth()->user()->hasRole('super_admin')) {
        abort(403);
    }

    $name = $department->name;
    $department->delete();

    return redirect()->route('master.departments.index')->with('success', "Instansi/OPD {$name} berhasil dihapus.");
  }
}
