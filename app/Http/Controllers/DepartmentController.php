<?php

namespace App\Http\Controllers;

use App\Models\Department;

class DepartmentController extends Controller
{
  public function show(Department $department)
  {
    $actor = auth()->user();
    $isSuperAdmin = $actor->hasRole('super_admin');

    // Admin OPD hanya melihat unit dalam lingkup datanya (instansinya + sub-unit yang berbagi data).
    $scope = $isSuperAdmin ? null : ($actor->department?->getScopedRelatedIds() ?? collect([$actor->department_id]));
    abort_unless($scope === null || $scope->contains($department->id), 403, 'Anda tidak memiliki akses ke halaman instansi/unit ini.');

    $department->load(['parent', 'children' => fn ($q) => $q->treeOrder()->withCount(['users', 'children'])])->loadCount('users');
    $canOpen = fn (?Department $dept) => $dept && ($scope === null || $scope->contains($dept->id));

    // Admin OPD tidak membuka daftar struktur: kembali ke unit induk bila masih dalam lingkupnya.
    $backUrl = $isSuperAdmin
      ? route('master.departments.index')
      : ($canOpen($department->parent) ? route('master.departments.show', $department->parent_id) : null);

    return view('master.departments.show', compact('department', 'canOpen', 'backUrl'));
  }

  /** Hapus unit (Super Admin): pegawainya dipindah ke unit induk. */
  public function destroy(Department $department)
  {
    abort_unless(auth()->user()->hasRole('super_admin'), 403, 'Unit kerja hanya dapat dihapus oleh Super Admin.');

    if ($error = $department->deletionError()) {
      return back()->with('error', "Gagal: {$error}");
    }

    $name = $department->name;
    $moved = $department->deleteIntoParent();

    return redirect()->route('master.departments.index')
      ->with('success', "Unit {$name} berhasil dihapus." . ($moved ? " {$moved} pegawai dipindah ke unit induknya." : ''));
  }
}
