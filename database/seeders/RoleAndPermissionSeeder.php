<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
  public function run(): void
  {
    // Reset cached roles and permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // ──────────────────────────────────────────────
    // Permissions
    // ──────────────────────────────────────────────

    $permissions = [
      // SPPD Management
      'sppd.create',
      'sppd.view',
      'sppd.view_all',
      'sppd.edit',
      'sppd.delete',
      'sppd.approve',
      'sppd.sign',

      // User Management
      'user.create',
      'user.view',
      'user.edit',
      'user.delete',

      // Department Management
      'department.create',
      'department.view',
      'department.edit',
      'department.delete',

      // Budget Management
      'budget.create',
      'budget.view',
      'budget.edit',
      'budget.delete',

      // Report Management
      'report.create',
      'report.view',
      'report.verify',

      // Settings
      'setting.manage',
    ];

    foreach ($permissions as $permission) {
      Permission::findOrCreate($permission);
    }


    // ──────────────────────────────────────────────
    // Roles
    // ──────────────────────────────────────────────

    // Super Admin — akses semua
    Role::firstOrCreate(
      ['name' => 'super_admin'],
      ['label' => 'Super Admin', 'guard_name' => 'web']
    )->givePermissionTo(Permission::all());

    // Admin OPD
    Role::firstOrCreate(
      ['name' => 'admin_opd'],
      ['label' => 'Admin OPD', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.create',
      'sppd.view',
      'sppd.view_all',
      'sppd.edit',
      'sppd.delete',
      'user.view',
      'user.create',
      'user.edit',
      'user.delete',
      'budget.view',
      'budget.create',
      'budget.edit',
      'budget.delete',
      'report.view',
    ]);

    // Kepala OPD
    Role::firstOrCreate(
      ['name' => 'kepala_opd'],
      ['label' => 'Kepala OPD', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'sppd.sign',
      'report.view',
    ]);

    // Sekretaris Dinas
    Role::firstOrCreate(
      ['name' => 'sekretaris_opd'],
      ['label' => 'Sekretaris OPD', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'report.view',
    ]);

    // Kasubid / Kasubag
    Role::firstOrCreate(
      ['name' => 'kasubid_kasubag'],
      ['label' => 'Kasubid / Kasubag', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'report.view',
    ]);

    // Kabid / Irban / Kabag
    Role::firstOrCreate(
      ['name' => 'kabid_irban_kabag'],
      ['label' => 'Kabid / Irban / Kabag', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'report.view',
    ]);

    // Pimpinan DPRD
    Role::firstOrCreate(
      ['name' => 'pimpinan_dprd'],
      ['label' => 'Pimpinan DPRD', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'sppd.sign',
      'report.view',
    ]);

    // Asisten
    Role::firstOrCreate(
      ['name' => 'asisten'],
      ['label' => 'Asisten', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'report.view',
    ]);

    // Sekda
    Role::firstOrCreate(
      ['name' => 'sekda'],
      ['label' => 'Sekda', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'sppd.sign',
      'report.view',
    ]);

    // Walikota
    Role::firstOrCreate(
      ['name' => 'walikota'],
      ['label' => 'Walikota', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'sppd.sign',
      'report.view',
    ]);

    // Wakil Walikota
    Role::firstOrCreate(
      ['name' => 'wakil_walikota'],
      ['label' => 'Wakil Walikota', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'sppd.sign',
      'report.view',
    ]);

    // Sekwan (Sekretaris Dewan)
    Role::firstOrCreate(
      ['name' => 'sekwan'],
      ['label' => 'Sekwan', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'report.view',
    ]);

    // Camat
    Role::firstOrCreate(
      ['name' => 'camat'],
      ['label' => 'Camat', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'sppd.sign',
      'report.view',
    ]);

    // Sekcam
    Role::firstOrCreate(
      ['name' => 'sekcam'],
      ['label' => 'Sekcam', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'sppd.sign',
      'report.view',
    ]);

    // Lurah
    Role::firstOrCreate(
      ['name' => 'lurah'],
      ['label' => 'Lurah', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'sppd.sign',
      'report.view',
    ]);

    // Kepala Puskesmas
    Role::firstOrCreate(
      ['name' => 'kapus'],
      ['label' => 'Kepala Puskesmas', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'sppd.sign',
      'report.view',
    ]);

    // Inspektorat
    Role::firstOrCreate(
      ['name' => 'inspektorat'],
      ['label' => 'Inspektorat', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.view',
      'sppd.view_all',
      'sppd.approve',
      'sppd.sign',
      'report.view',
    ]);

    // Anggota DPRD
    Role::firstOrCreate(
      ['name' => 'anggota_dprd'],
      ['label' => 'Anggota DPRD', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.create',
      'sppd.view',
      'report.create',
      'report.view',
    ]);

    //  Staf — pengguna biasa
    Role::firstOrCreate(
      ['name' => 'staf'],
      ['label' => 'Staf', 'guard_name' => 'web']
    )->givePermissionTo([
      'sppd.create',
      'sppd.view',
      'sppd.edit',
      'report.create',
      'report.view',
    ]);

    // ──────────────────────────────────────────────
    // Warna badge per role (token Tailwind, lihat App\Support\BadgeColor)
    // ──────────────────────────────────────────────
    // Warna sengaja disebar agar role yang bisa tampil dalam satu OPD selalu
    // jelas berbeda (tidak ada hue yang mirip dalam satu grup).
    $roleColors = [
      'super_admin'       => 'red',
      'admin_opd'         => 'blue',
      'kepala_opd'        => 'green',
      'sekretaris_opd'    => 'orange',
      'kabid_irban_kabag' => 'violet',
      'kasubid_kasubag'   => 'pink',
      'pimpinan_dprd'     => 'indigo',
      'anggota_dprd'      => 'fuchsia',
      'asisten'           => 'lime',
      'sekda'             => 'cyan',
      'walikota'          => 'rose',
      'wakil_walikota'    => 'amber',
      'sekwan'            => 'teal',
      'camat'             => 'emerald',
      'sekcam'            => 'yellow',
      'lurah'             => 'sky',
      'kapus'             => 'purple',
      // 'rose' dipakai ulang dgn walikota — keduanya tak pernah satu OPD.
      'inspektorat'       => 'rose',
      'staf'              => 'slate',
    ];

    foreach ($roleColors as $name => $color) {
      Role::where('name', $name)->update(['color' => $color]);
    }
  }
}
