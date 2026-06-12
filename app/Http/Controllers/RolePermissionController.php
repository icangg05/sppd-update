<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('master.roles.index', compact('roles', 'permissions'));
    }

    public function destroy(Role $role)
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        if ($role->name === 'super_admin') {
            return back()->withErrors(['role' => 'Role super_admin tidak dapat dihapus.']);
        }

        $name = $role->label ?? $role->name;
        $role->delete();

        return redirect()->route('master.roles.index')
            ->with('success', "Role \"{$name}\" berhasil dihapus.");
    }

}
