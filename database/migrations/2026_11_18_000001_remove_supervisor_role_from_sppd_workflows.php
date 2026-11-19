<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kriteria alur "Atasan langsung pemohon" dihapus: alur dipilih dari tipe instansi,
 * peran pemohon, dan wilayah tujuan saja.
 *
 * Alur yang dulu dipecah per atasan disatukan ke versi lengkap yang melewati Kabid/
 * Irban/Kabag (Staf & Kasubag selalu lewat Kabid). Varian beratasan pejabat lain
 * (Sekretaris/Asisten/Sekwan) dihapus hanya bila ada versi Kabid dengan cakupan yang
 * sama persis; varian tanpa pasangan tetap ada, cukup dilepas kriterianya. Akhiran
 * "(atasan …)" pada nama dibuang. SPPD yang sudah ada tidak terpengaruh karena
 * tahap persetujuannya tersimpan per SPPD.
 */
return new class extends Migration
{
  public function up(): void
  {
    DB::transaction(function () {
      $workflows = DB::table('sppd_workflows')->whereNotNull('supervisor_role')->orderBy('id')->get();

      // Cakupan dibandingkan tanpa memedulikan urutan centang.
      $scope = function (object $w): string {
        $norm = function (?string $json) {
          $values = json_decode($json ?? 'null', true);
          if (is_array($values)) {
            sort($values);
          }

          return $values;
        };

        return json_encode([$norm($w->department_type), $norm($w->applicant_role), $norm($w->destination)]);
      };
      $viaKabid = fn (object $w) => in_array('kabid_irban_kabag', json_decode($w->supervisor_role, true) ?: [], true);
      $kabidScopes = $workflows->filter($viaKabid)->map($scope)->all();

      foreach ($workflows as $workflow) {
        if (! $viaKabid($workflow) && in_array($scope($workflow), $kabidScopes, true)) {
          DB::table('sppd_workflows')->where('id', $workflow->id)->delete();
          continue;
        }

        DB::table('sppd_workflows')->where('id', $workflow->id)->update([
          'name'       => trim(preg_replace('/\s*\(atasan [^)]*\)/u', '', $workflow->name)),
          'updated_at' => now(),
        ]);
      }
    });

    Schema::table('sppd_workflows', function (Blueprint $table) {
      $table->dropColumn('supervisor_role');
    });
  }

  /** Kolom dikembalikan kosong ("tidak dibedakan"); alur yang dihapus tidak dipulihkan. */
  public function down(): void
  {
    Schema::table('sppd_workflows', function (Blueprint $table) {
      $table->json('supervisor_role')->nullable()->after('destination');
    });
  }
};
