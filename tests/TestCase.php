<?php

namespace Tests;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /**
     * Beri role yang sudah dibuat test permission bawaannya (RoleAndPermissionSeeder::ROLES).
     * Dipakai test yang membuat role sendiri lewat Role::firstOrCreate tanpa permission,
     * padahal aksi yang diuji (buat SPPD, kelola biaya, cek TTE) diperiksa lewat permission.
     */
    protected function grantDefaultPermissions(): void
    {
        foreach (RoleAndPermissionSeeder::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (Role::all() as $role) {
            $role->givePermissionTo(RoleAndPermissionSeeder::permissionsFor($role->name));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
