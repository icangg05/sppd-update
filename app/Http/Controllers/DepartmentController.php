<?php

namespace App\Http\Controllers;

use App\Models\Department;

class DepartmentController extends Controller
{
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
