<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
  public function index(Request $request)
  {
    $query = User::with(['department', 'rank', 'position', 'roles']);

    // Filter berdasarkan instansi user jika bukan super admin
    if (!auth()->user()->hasRole('super_admin')) {
      $query->where('department_id', auth()->user()->department_id);
    }

    if ($request->filled('search')) {
      $s = $request->search;
      $query->where(function ($q) use ($s) {
        $q->where('name', 'like', "%{$s}%")
          ->orWhere('username', 'like', "%{$s}%")
          ->orWhere('nip', 'like', "%{$s}%")
          ->orWhere('email', 'like', "%{$s}%");
      });
    }

    if ($request->filled('department_id') && auth()->user()->hasRole('super_admin')) {
      $query->where('department_id', $request->department_id);
    }

    $users = $query->orderBy('name')->paginate(20)->withQueryString();
    
    // Dropdown department hanya untuk super admin atau minimal tampilkan department sendiri
    $departments = auth()->user()->hasRole('super_admin') 
      ? Department::orderBy('name')->get() 
      : collect([auth()->user()->department])->filter();

    return view('master.users.index', compact('users', 'departments'));
  }

  public function create()
  {
    $departments = Department::orderBy('name')->get();
    $ranks = Rank::orderBy('group')->get();
    $positions = Position::orderBy('name')->get();

    return view('master.users.create', compact('departments', 'ranks', 'positions'));
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name'          => 'required|string|max:255',
      'username'      => 'required|string|max:255|unique:users,username',
      'email'         => 'required|email|unique:users,email',
      'password'      => 'required|string|min:6',
      'nip'           => 'nullable|string|max:20',
      'phone'         => 'nullable|string|max:20',
      'employee_type' => 'required|in:' . implode(',', array_column(\App\Enums\EmployeeType::cases(), 'value')),
      'department_id' => 'nullable|exists:departments,id',
      'rank_id'       => 'nullable|exists:ranks,id',
      'position_id'   => 'nullable|exists:positions,id',
      'position_name' => 'nullable|string|max:255',
      'role'          => 'required|string',
    ]);

    if (!auth()->user()->hasRole('super_admin')) {
        $validated['department_id'] = auth()->user()->department_id;
    }

    $user = User::create([
      ...$validated,
      'password'  => Hash::make($validated['password']),
      'is_active' => true,
    ]);

    $user->assignRole($validated['role']);

    return redirect()->route('master.users.index')->with('success', "Pegawai {$user->name} berhasil ditambahkan.");
  }

  public function show(User $user)
  {
    $user->load(['department', 'rank', 'position', 'roles']);
    return view('master.users.show', compact('user'));
  }

  public function edit(User $user)
  {
    $departments = Department::orderBy('name')->get();
    $ranks = Rank::orderBy('group')->get();
    $positions = Position::orderBy('name')->get();
    
    return view('master.users.edit', compact('user', 'departments', 'ranks', 'positions'));
  }

  public function update(Request $request, User $user)
  {
    $validated = $request->validate([
      'name'          => 'required|string|max:255',
      'username'      => 'required|string|max:255|unique:users,username,' . $user->id,
      'email'         => 'required|email|unique:users,email,' . $user->id,
      'password'      => 'nullable|string|min:6',
      'nip'           => 'nullable|string|max:20',
      'phone'         => 'nullable|string|max:20',
      'employee_type' => 'required|in:' . implode(',', array_column(\App\Enums\EmployeeType::cases(), 'value')),
      'department_id' => 'nullable|exists:departments,id',
      'rank_id'       => 'nullable|exists:ranks,id',
      'position_id'   => 'nullable|exists:positions,id',
      'position_name' => 'nullable|string|max:255',
      'role'          => 'required|string',
    ]);

    if (!auth()->user()->hasRole('super_admin')) {
        $validated['department_id'] = auth()->user()->department_id;
    }

    $data = $validated;
    if (!empty($data['password'])) {
        $data['password'] = Hash::make($data['password']);
    } else {
        unset($data['password']);
    }

    $user->update($data);
    $user->syncRoles([$validated['role']]);

    return redirect()->route('master.users.index')->with('success', "Pegawai {$user->name} berhasil diperbarui.");
  }

  public function destroy(User $user)
  {
    $name = $user->name;
    $user->delete();

    return redirect()->route('master.users.index')->with('success', "Pegawai {$name} berhasil dihapus.");
  }

  public function toggleActive(User $user)
  {
    $user->update(['is_active' => !$user->is_active]);
    $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

    return back()->with('success', "Pegawai {$user->name} berhasil {$status}.");
  }
}
