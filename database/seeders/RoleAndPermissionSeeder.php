<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
  public const PERMISSIONS = [
    // SPPD Management
    'sppd.create',
    'sppd.view',
    'sppd.view_all',
    'sppd.edit',
    'sppd.delete',
    'sppd.approve',
    'sppd.sign',

    // Menu Selanjutnya: dokumen & biaya perjalanan (lihat App\Support\PermissionCatalog)
    'cost.view',
    'cost.manage',

    // Cek Dokumen TTE
    'document.verify',

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

  /** Hak akses pejabat penyetuju (tanpa TTE). */
  private const APPROVER = ['sppd.view', 'sppd.view_all', 'sppd.approve', 'cost.view', 'report.view'];

  /** Hak akses pejabat penyetuju yang juga menandatangani TTE. */
  private const SIGNER = [...self::APPROVER, 'sppd.sign'];

  /**
   * Permission bawaan per role ('*' = semua). Super Admin dapat mengubahnya di
   * menu Role & Permission; seeder hanya menambah, tidak mencabut.
   *
   * Pembuatan SPPD & pengelolaan biaya dipegang Kasubid/Kasubag. Admin OPD masih
   * memegangnya selama masa peralihan sampai Super Admin mencabutnya.
   *
   * @var array<string, array{label: string, permissions: string|array<int, string>}>
   */
  public const ROLES = [
    'super_admin' => ['label' => 'Super Admin', 'permissions' => '*'],
    'admin_opd' => ['label' => 'Admin OPD', 'permissions' => [
      'sppd.create', 'sppd.view', 'sppd.view_all', 'sppd.edit', 'sppd.delete',
      'cost.view', 'cost.manage', 'document.verify',
      'user.view', 'user.create', 'user.edit', 'user.delete',
      'budget.view', 'budget.create', 'budget.edit', 'budget.delete',
      'report.view',
    ]],
    'kepala_opd' => ['label' => 'Kepala OPD', 'permissions' => self::SIGNER,
      'note' => 'Di unit kerja Rumah Sakit, role ini disebut Direktur (Direktur RSUD memakai role ini).'],
    'sekretaris_opd' => ['label' => 'Sekretaris OPD', 'permissions' => self::APPROVER,
      'note' => 'Di unit kerja Rumah Sakit, role ini disebut Sekretaris (Sekretaris RSUD memakai role ini).'],
    // Kasubid/Kasubag tidak pernah menjadi tahap alur persetujuan (lihat WorkflowForm),
    // jadi tanpa sppd.approve; perannya membuat SPPD & mengelola biaya perjalanan.
    'kasubid_kasubag' => ['label' => 'Kasubid / Kasubag', 'permissions' => [
      'sppd.create', 'sppd.view', 'sppd.view_all', 'sppd.delete',
      'cost.view', 'cost.manage', 'document.verify',
      'report.view',
    ]],
    'kabid_irban_kabag' => ['label' => 'Kabid / Irban / Kabag', 'permissions' => self::APPROVER],
    'pimpinan_dprd' => ['label' => 'Pimpinan DPRD', 'permissions' => self::SIGNER],
    'asisten' => ['label' => 'Asisten', 'permissions' => self::APPROVER],
    'sekda' => ['label' => 'Sekda', 'permissions' => self::SIGNER],
    'walikota' => ['label' => 'Walikota', 'permissions' => self::SIGNER],
    'wakil_walikota' => ['label' => 'Wakil Walikota', 'permissions' => self::SIGNER],
    'sekwan' => ['label' => 'Sekwan', 'permissions' => self::APPROVER],
    'camat' => ['label' => 'Camat', 'permissions' => self::SIGNER],
    // Role khusus Pimpinan/Sekretaris per tipe unit (DepartmentType::specificRole); izinnya
    // setara Kepala OPD / Sekretaris OPD.
    'sekcam' => ['label' => 'Sekcam', 'permissions' => self::APPROVER],
    'lurah' => ['label' => 'Lurah', 'permissions' => self::SIGNER],
    'seklur' => ['label' => 'Seklur', 'permissions' => self::APPROVER],
    'kapus' => ['label' => 'Kepala Puskesmas', 'permissions' => self::SIGNER],
    'anggota_dprd' => ['label' => 'Anggota DPRD', 'permissions' => ['sppd.view', 'cost.view', 'report.create', 'report.view']],
    'staf' => ['label' => 'Staf', 'permissions' => ['sppd.view', 'sppd.edit', 'cost.view', 'report.create', 'report.view']],
    // Tenaga Mitra hanya pengikut perjalanan dan tidak punya akun login, jadi tanpa permission.
    User::TENAGA_MITRA_ROLE => ['label' => 'Tenaga Mitra', 'permissions' => [],
      'note' => 'Orang dari luar OPD yang hanya menjadi pengikut perjalanan dinas, tanpa akun login. Diberikan lewat mode Tenaga Mitra di form Tambah Pegawai, tidak bisa menjadi pelaksana.'],
  ];

  public function run(): void
  {
    // Reset cached roles and permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (self::PERMISSIONS as $permission) {
      Permission::findOrCreate($permission);
    }

    foreach (self::ROLES as $name => $role) {
      Role::firstOrCreate(
        ['name' => $name],
        ['label' => $role['label'], 'note' => $role['note'] ?? null, 'guard_name' => 'web']
      )->givePermissionTo(self::permissionsFor($name));
    }

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
      'seklur'            => 'yellow',
      'lurah'             => 'sky',
      'kapus'             => 'purple',
      'staf'              => 'slate',
      // Amber juga dipakai Wakil Walikota, yang dikelola di menunya sendiri dan tidak pernah
      // tampil di daftar Pegawai bersama Tenaga Mitra.
      User::TENAGA_MITRA_ROLE => 'amber',
    ];

    foreach ($roleColors as $name => $color) {
      Role::where('name', $name)->update(['color' => $color]);
    }
  }

  /** @return array<int, string> */
  public static function permissionsFor(string $role): array
  {
    $permissions = self::ROLES[$role]['permissions'] ?? [];

    return $permissions === '*' ? self::PERMISSIONS : $permissions;
  }
}
