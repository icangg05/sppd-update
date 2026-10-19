<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lurah, Camat, dan Kepala Puskesmas kini memakai role Kepala OPD, sehingga
 * pengajuan SPPD mereka mengikuti alur yang sama dengan Kepala OPD.
 */
return new class extends Migration
{
  private const TYPES = ['kecamatan', 'kelurahan', 'puskesmas'];

  public function up(): void
  {
    $this->eachKepalaOpdWorkflow(fn (array $types) => array_values(array_unique([...$types, ...self::TYPES])));
  }

  public function down(): void
  {
    $this->eachKepalaOpdWorkflow(fn (array $types) => array_values(array_diff($types, self::TYPES)));
  }

  private function eachKepalaOpdWorkflow(callable $transform): void
  {
    DB::table('sppd_workflows')->where('name', 'Kepala OPD')->orderBy('id')->get()
      ->each(function ($workflow) use ($transform) {
        $applicants = json_decode($workflow->applicant_role ?? '[]', true) ?: [];
        $types = json_decode($workflow->department_type ?? 'null', true);

        // Lewati yang bukan untuk pemohon Kepala OPD, atau yang berlaku untuk semua jenis unit.
        if (! in_array('kepala_opd', $applicants, true) || empty($types)) {
          return;
        }

        DB::table('sppd_workflows')->where('id', $workflow->id)
          ->update(['department_type' => json_encode($transform($types))]);
      });
  }
};
