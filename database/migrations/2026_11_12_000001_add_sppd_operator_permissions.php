<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Hak membuat SPPD & mengelola biaya perjalanan kini diatur lewat permission
 * (menu Role & Permission), bukan dikunci ke role admin_opd di kode.
 *
 *  - cost.view       : membuka menu Selanjutnya (lihat & cetak dokumen biaya/laporan).
 *  - cost.manage     : mengisi PPTK, Setuju Bayar, panjar, pengeluaran riil, rincian biaya, laporan.
 *  - document.verify : menu Cek Dokumen TTE.
 *
 * Kasubid/Kasubag mendapat hak operator SPPD. Admin OPD tetap memegangnya pada masa
 * peralihan sampai Super Admin mencabutnya. Akses yang berlaku sebelumnya dipertahankan:
 * semua role tetap bisa membuka menu Selanjutnya, dan sppd.create/sppd.delete dicabut dari
 * role yang memegangnya tanpa efek (dulu pemeriksaannya memakai role admin_opd/super_admin),
 * agar tidak tiba-tiba membuka form Buat SPPD bagi staf atau anggota DPRD.
 */
return new class extends Migration
{
  private const NEW = ['cost.view', 'cost.manage', 'document.verify'];

  private const OPERATOR_ROLES = ['super_admin', 'admin_opd', 'kasubid_kasubag'];

  public function up(): void
  {
    $registrar = app(PermissionRegistrar::class);
    $registrar->forgetCachedPermissions();

    foreach ([...self::NEW, 'sppd.create', 'sppd.delete'] as $name) {
      Permission::findOrCreate($name, 'web');
    }

    foreach (Role::where('guard_name', 'web')->get() as $role) {
      if (in_array($role->name, self::OPERATOR_ROLES, true)) {
        $role->givePermissionTo([...self::NEW, 'sppd.create', 'sppd.delete']);
      } else {
        $role->givePermissionTo('cost.view');
        $role->revokePermissionTo(['sppd.create', 'sppd.delete']);
      }
    }

    // Kasubid/Kasubag tidak pernah menjadi tahap alur persetujuan; tanpa sppd.approve
    // menu Menunggu Persetujuan yang selalu kosong tidak tampil lagi.
    Role::where('name', 'kasubid_kasubag')->where('guard_name', 'web')->first()?->revokePermissionTo('sppd.approve');

    $registrar->forgetCachedPermissions();
  }

  public function down(): void
  {
    $registrar = app(PermissionRegistrar::class);
    $registrar->forgetCachedPermissions();

    $kasubag = Role::where('name', 'kasubid_kasubag')->where('guard_name', 'web')->first();
    if ($kasubag) {
      $kasubag->revokePermissionTo(['sppd.create', 'sppd.delete']);
      $kasubag->givePermissionTo(Permission::findOrCreate('sppd.approve', 'web'));
    }

    foreach (['staf', 'anggota_dprd'] as $name) {
      Role::where('name', $name)->where('guard_name', 'web')->first()?->givePermissionTo('sppd.create');
    }

    Permission::whereIn('name', self::NEW)->where('guard_name', 'web')->get()->each->delete();

    $registrar->forgetCachedPermissions();
  }
};
