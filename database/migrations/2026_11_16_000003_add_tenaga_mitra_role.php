<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role Tenaga Mitra: orang dari luar OPD (mis. akademisi) yang hanya diikutkan sebagai
 * pengikut perjalanan dinas. Role ini sekaligus penandanya (lihat User::isTenagaMitra) dan
 * hanya diberikan lewat mode Tenaga Mitra di form Tambah Pegawai, bukan lewat pilihan role.
 *
 * Tanpa permission: Tenaga Mitra tidak punya akun login (tanpa username).
 */
return new class extends Migration
{
  // Nama role ditulis langsung, bukan dari konstanta di model User, agar migrasi tidak
  // ikut berubah bila konstanta itu diganti.
  private const ROLE = 'tenaga_mitra';

  private const NOTE = 'Orang dari luar OPD yang hanya menjadi pengikut perjalanan dinas, tanpa akun login. '
    . 'Diberikan lewat mode Tenaga Mitra di form Tambah Pegawai, tidak bisa menjadi pelaksana.';

  public function up(): void
  {
    $registrar = app(PermissionRegistrar::class);
    $registrar->forgetCachedPermissions();

    $role = Role::firstOrCreate(
      ['name' => self::ROLE, 'guard_name' => 'web'],
      ['label' => 'Tenaga Mitra'],
    );

    $table = config('permission.table_names.roles', 'roles');
    $extra = ['color' => 'amber'];
    if (Schema::hasColumn($table, 'note')) {
      $extra['note'] = self::NOTE;
    }
    DB::table($table)->where('id', $role->id)->update($extra);

    $registrar->forgetCachedPermissions();
  }

  public function down(): void
  {
    $registrar = app(PermissionRegistrar::class);
    $registrar->forgetCachedPermissions();

    // Role yang masih dipegang tidak dihapus agar datanya tidak kehilangan penanda.
    $role = Role::where('name', self::ROLE)->where('guard_name', 'web')->first();
    if ($role && ! $role->users()->exists()) {
      $role->delete();
    }

    $registrar->forgetCachedPermissions();
  }
};
