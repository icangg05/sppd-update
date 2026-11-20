<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Pimpinan & Sekretaris di Kecamatan, Kelurahan, dan Puskesmas memakai role khusus unitnya
 * (keputusan user 2026-11-20): Camat/Sekcam, Lurah/Seklur, Kepala Puskesmas. Sebelumnya tingkat
 * jabatan Pimpinan/Sekretaris selalu menjadi Kepala OPD/Sekretaris OPD sehingga role khusus tak
 * pernah terpakai. Alur tetap menulis tahap kepala_opd/sekretaris_opd dan mengenali role khusus
 * lewat SppdWorkflowService::getRoleSynonyms.
 *
 * 1. Role khusus (termasuk Seklur yang baru) mendapat izin yang sama dengan role umumnya saat ini.
 * 2. Alur yang menerima pemohon Sekcam ikut menerima Seklur.
 * 3. Pemegang Kepala/Sekretaris OPD di ketiga tipe unit itu dipindah ke role khususnya, tercatat
 *    di log aktivitas (format sama dengan SppdWorkflowService, dibaca SnapshotSppdPeople).
 */
return new class extends Migration
{
  // Ditulis langsung (bukan dari DepartmentType::specificRole) agar migrasi tidak ikut berubah.
  private const MAP = [
    'kecamatan' => ['kepala_opd' => 'camat', 'sekretaris_opd' => 'sekcam'],
    'kelurahan' => ['kepala_opd' => 'lurah', 'sekretaris_opd' => 'seklur'],
    'puskesmas' => ['kepala_opd' => 'kapus'],
  ];

  private const LABELS = [
    'camat' => 'Camat', 'sekcam' => 'Sekcam', 'lurah' => 'Lurah', 'seklur' => 'Seklur', 'kapus' => 'Kepala Puskesmas',
  ];

  public function up(): void
  {
    $registrar = app(PermissionRegistrar::class);
    $registrar->forgetCachedPermissions();

    $this->syncSpecificRoles();
    $this->addSeklurToWorkflows();
    $this->moveHolders(fn (string $type, string $role) => self::MAP[$type][$role] ?? null, 'disesuaikan dengan tipe unit kerja');

    $registrar->forgetCachedPermissions();
  }

  public function down(): void
  {
    $registrar = app(PermissionRegistrar::class);
    $registrar->forgetCachedPermissions();

    $this->moveHolders(fn (string $type, string $role) => array_search($role, self::MAP[$type], true) ?: null, 'dikembalikan ke role umum');

    foreach (DB::table('sppd_workflows')->get(['id', 'applicant_role']) as $workflow) {
      $roles = json_decode($workflow->applicant_role ?? 'null', true);
      if (is_array($roles) && in_array('seklur', $roles, true)) {
        DB::table('sppd_workflows')->where('id', $workflow->id)
          ->update(['applicant_role' => json_encode(array_values(array_diff($roles, ['seklur'])))]);
      }
    }

    $seklur = Role::where('name', 'seklur')->where('guard_name', 'web')->first();
    if ($seklur && ! $seklur->users()->exists()) {
      $seklur->delete();
    }

    $registrar->forgetCachedPermissions();
  }

  /** Role khusus ada dan izinnya setara role umum di database (Super Admin bisa mengubah izin). */
  private function syncSpecificRoles(): void
  {
    $hasColor = Schema::hasColumn(config('permission.table_names.roles', 'roles'), 'color');

    foreach (self::MAP as $pairs) {
      foreach ($pairs as $generic => $specific) {
        // Database baru (belum di-seed) dilewati: RoleAndPermissionSeeder membuat semuanya.
        $genericRole = Role::where('name', $generic)->where('guard_name', 'web')->first();
        if (! $genericRole) {
          continue;
        }

        $role = Role::firstOrCreate(['name' => $specific, 'guard_name' => 'web'], ['label' => self::LABELS[$specific]]);
        if ($role->wasRecentlyCreated && $hasColor) {
          DB::table(config('permission.table_names.roles', 'roles'))->where('id', $role->id)->update(['color' => 'yellow']);
        }
        $role->syncPermissions($genericRole->permissions);
      }
    }
  }

  private function addSeklurToWorkflows(): void
  {
    foreach (DB::table('sppd_workflows')->get(['id', 'applicant_role']) as $workflow) {
      $roles = json_decode($workflow->applicant_role ?? 'null', true);
      if (is_array($roles) && in_array('sekcam', $roles, true) && ! in_array('seklur', $roles, true)) {
        DB::table('sppd_workflows')->where('id', $workflow->id)
          ->update(['applicant_role' => json_encode([...$roles, 'seklur'])]);
      }
    }
  }

  /**
   * Ganti role pegawai di unit Kecamatan/Kelurahan/Puskesmas menurut $target(tipe unit, role)
   * (null = tidak diganti), lalu catat perubahannya.
   */
  private function moveHolders(\Closure $target, string $reason): void
  {
    $existing = Role::where('guard_name', 'web')->pluck('name')->all();

    $users = User::with(['roles', 'department'])
      ->whereHas('department', fn ($d) => $d->whereIn('type', array_keys(self::MAP)))
      ->get();

    foreach ($users as $user) {
      $type = $user->department->type?->value;
      $old = $user->roles->pluck('name')->all();
      $new = array_map(function (string $role) use ($target, $type, $existing) {
        $replacement = $target($type, $role);

        return $replacement && in_array($replacement, $existing, true) ? $replacement : $role;
      }, $old);

      if ($new === $old) {
        continue;
      }

      $user->syncRoles($new);

      activity()
        ->performedOn($user)
        ->event('updated')
        ->withChanges(['old' => ['role' => implode(', ', $old)], 'attributes' => ['role' => implode(', ', $new)]])
        ->log("Role {$user->name} {$reason}: " . implode(', ', $old) . ' → ' . implode(', ', $new));
    }
  }
};
