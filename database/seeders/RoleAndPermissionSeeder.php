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
    Role::findOrCreate('super_admin')
      ->givePermissionTo(Permission::all());

    // Admin OPD — mengelola SPPD di instansi sendiri
    Role::findOrCreate('admin_opd')
      ->givePermissionTo([
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

    // Kepala OPD — approve & sign SPPD
    Role::findOrCreate('kepala_opd')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'sppd.approve',
        'sppd.sign',
        'report.view',
      ]);

    // Sekretaris Dinas
    Role::findOrCreate('sekretaris')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'sppd.approve',
        'report.view',
      ]);

    // Kasubag
    Role::findOrCreate('kasubag')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'sppd.approve',
        'report.view',
      ]);

    // Kabag
    Role::findOrCreate('kabag')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'sppd.approve',
        'report.view',
      ]);

    // Asisten
    Role::findOrCreate('asisten')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'sppd.approve',
        'report.view',
      ]);

    // Sekda
    Role::findOrCreate('sekda')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'sppd.approve',
        'sppd.sign',
        'report.view',
      ]);

    // Walikota
    Role::findOrCreate('walikota')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'sppd.approve',
        'sppd.sign',
        'report.view',
      ]);

    // Staff — pengguna biasa
    Role::findOrCreate('staff')
      ->givePermissionTo([
        'sppd.create',
        'sppd.view',
        'sppd.edit',
        'report.create',
        'report.view',
      ]);

    // Staff DPRD
    Role::findOrCreate('staff_dprd')
      ->givePermissionTo([
        'sppd.create',
        'sppd.view',
        'sppd.edit',
        'report.create',
        'report.view',
      ]);

    // Anggota DPRD
    Role::findOrCreate('anggota_dprd')
      ->givePermissionTo([
        'sppd.view',
        'report.view',
      ]);

    // Pimpinan DPRD
    Role::findOrCreate('pimpinan_dprd')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'sppd.approve',
        'sppd.sign',
        'report.view',
      ]);

    // Sekwan (Sekretaris Dewan)
    Role::findOrCreate('sekwan')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'sppd.approve',
        'report.view',
      ]);

    // Camat
    Role::findOrCreate('camat')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'sppd.approve',
        'sppd.sign',
        'report.view',
      ]);

    // Lurah
    Role::findOrCreate('lurah')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'sppd.approve',
        'sppd.sign',
        'report.view',
      ]);

    // Kepala Puskesmas
    Role::findOrCreate('kapus')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'sppd.approve',
        'sppd.sign',
        'report.view',
      ]);

    // Bendahara
    Role::findOrCreate('bendahara')
      ->givePermissionTo([
        'sppd.view',
        'budget.view',
        'report.view',
        'report.verify',
      ]);

    // PPTK
    Role::findOrCreate('pptk')
      ->givePermissionTo([
        'sppd.view',
        'sppd.view_all',
        'budget.view',
        'report.view',
      ]);
  }
}
