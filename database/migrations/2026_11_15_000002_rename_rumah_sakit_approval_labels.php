<?php

use App\Enums\DepartmentType;
use App\Models\Department;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Di Rumah Sakit, pejabat ber-role Kepala OPD disebut "Direktur" dan Sekretaris OPD
 * "Sekretaris" (DepartmentType::roleTitle). Persetujuan baru sudah memakai sebutan itu;
 * migrasi ini menyamakan label persetujuan lama yang pejabatnya berada di zona data
 * Rumah Sakit (tampil di detail SPPD & pesan Telegram). Berjalan setelah
 * 2026_11_15_000001 yang mengubah tipe unit RSUD menjadi rumah_sakit.
 */
return new class extends Migration
{
  private const TITLES = ['kepala_opd' => 'Direktur', 'sekretaris_opd' => 'Sekretaris'];

  /** Persetujuan yang pejabatnya berada di zona data unit Rumah Sakit. */
  private function hospitalApprovals(): \Illuminate\Database\Query\Builder
  {
    $departmentIds = Department::with('parent')->get()
      ->filter(fn (Department $d) => $d->getScopeRootDepartment()->type === DepartmentType::RUMAH_SAKIT)
      ->modelKeys();

    return DB::table('sppd_approvals')
      ->whereIn('approver_id', DB::table('users')->select('id')->whereIn('department_id', $departmentIds ?: [0]));
  }

  /** @return array<string, string> label role => sebutan Rumah Sakit */
  private function renames(): array
  {
    $labels = DB::table('roles')->whereIn('name', array_keys(self::TITLES))->pluck('label', 'name');

    return collect(self::TITLES)
      ->filter(fn (string $title, string $role) => filled($labels[$role] ?? null))
      ->mapWithKeys(fn (string $title, string $role) => [$labels[$role] => $title])
      ->all();
  }

  public function up(): void
  {
    foreach ($this->renames() as $from => $to) {
      $this->hospitalApprovals()->where('role_label', $from)->update(['role_label' => $to]);
    }
  }

  public function down(): void
  {
    foreach ($this->renames() as $from => $to) {
      $this->hospitalApprovals()->where('role_label', $to)->update(['role_label' => $from]);
    }
  }
};
