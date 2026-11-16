<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Alur persetujuan khusus Rumah Sakit (tipe unit kerja baru `rumah_sakit`).
 *
 *  - Rumah sakit (unit akar bernama "Rumah Sakit…" atau "RSUD…", mis. "Rumah Sakit Umum
 *    Daerah Kota Kendari" dan "RSUD Antero Hamra") berpindah dari tipe `opd` ke
 *    `rumah_sakit`, sehingga alurnya terpisah dari OPD biasa.
 *  - Dinas Kesehatan berpindah dari `opd` ke `dinkes`: tahap Kepala Dinas Kesehatan
 *    (role tahap `kepala_opd@dinkes`) mencari pejabatnya di unit akar bertipe `dinkes`.
 *    Semua alur OPD yang ada sudah mencakup `dinkes`, jadi alur Dinas Kesehatan tidak berubah.
 *  - Tujuh alur Rumah Sakit disisipkan bila belum ada. Alur di database sudah dikustom
 *    Super Admin, jadi tidak di-reseed; instalasi baru (tabel alur kosong) memakai
 *    SppdWorkflowSeeder.
 */
return new class extends Migration
{
  private const RS = ['rumah_sakit'];

  /** Unit akar yang namanya menandakan rumah sakit. */
  private function hospitals(string $type): \Illuminate\Database\Query\Builder
  {
    return DB::table('departments')->whereNull('parent_id')->where('type', $type)
      ->where(fn ($q) => $q->where('name', 'like', 'Rumah Sakit%')->orWhere('name', 'like', 'RSUD%'));
  }

  private function step(string $role, bool $spt = false, bool $sppd = false): array
  {
    return ['role' => $role, 'signs_spt' => $spt, 'signs_sppd' => $sppd];
  }

  /** @return array<int, array<string, mixed>> */
  private function workflows(): array
  {
    $staf = ['staf', 'kasubid_kasubag', 'kabid_irban_kabag'];
    $nonLn = ['dalam_daerah', 'lddp', 'ldlp'];
    $keWalikota = [$this->step('sekda'), $this->step('walikota', spt: true)];

    return [
      ['Staf, Kasubag & Kabid Rumah Sakit', $staf, $nonLn,
        [$this->step('sekretaris_opd'), $this->step('kepala_opd', spt: true, sppd: true)]],
      ['Staf, Kasubag & Kabid Rumah Sakit — Luar Negeri', $staf, ['luar_negeri'],
        [$this->step('sekretaris_opd'), $this->step('kepala_opd', sppd: true), ...$keWalikota]],
      ['Sekretaris Rumah Sakit', ['sekretaris_opd'], $nonLn,
        [$this->step('kepala_opd', spt: true, sppd: true)]],
      ['Sekretaris Rumah Sakit — Luar Negeri', ['sekretaris_opd'], ['luar_negeri'],
        [$this->step('kepala_opd', sppd: true), ...$keWalikota]],
      ['Direktur Rumah Sakit — Dalam Daerah', ['kepala_opd'], ['dalam_daerah'],
        [$this->step('sekretaris_opd'), $this->step('kepala_opd', spt: true, sppd: true)]],
      ['Direktur Rumah Sakit — Luar Daerah', ['kepala_opd'], ['lddp', 'ldlp'],
        [$this->step('sekretaris_opd'), $this->step('kepala_opd', sppd: true), $this->step('kepala_opd@dinkes', spt: true)]],
      ['Direktur Rumah Sakit — Luar Negeri', ['kepala_opd'], ['luar_negeri'],
        [$this->step('sekretaris_opd'), $this->step('kepala_opd', sppd: true), $this->step('kepala_opd@dinkes'), ...$keWalikota]],
    ];
  }

  public function up(): void
  {
    DB::transaction(function () {
      DB::table('departments')->whereNull('parent_id')->where('type', 'opd')
        ->where('name', 'Dinas Kesehatan')->update(['type' => 'dinkes', 'updated_at' => now()]);
      $this->hospitals('opd')->update(['type' => 'rumah_sakit', 'updated_at' => now()]);

      if (! DB::table('sppd_workflows')->exists()) {
        return;
      }

      foreach ($this->workflows() as [$name, $applicants, $destination, $steps]) {
        if (DB::table('sppd_workflows')->where('name', $name)->exists()) {
          continue;
        }

        DB::table('sppd_workflows')->insert([
          'name'            => $name,
          'department_type' => json_encode(self::RS),
          'applicant_role'  => json_encode($applicants),
          'destination'     => json_encode($destination),
          'supervisor_role' => null,
          'steps'           => json_encode($steps),
          'is_active'       => true,
          'created_at'      => now(),
          'updated_at'      => now(),
        ]);
      }
    });
  }

  public function down(): void
  {
    DB::transaction(function () {
      DB::table('sppd_workflows')->whereIn('name', array_column($this->workflows(), 0))->delete();

      DB::table('departments')->whereNull('parent_id')->where('type', 'dinkes')
        ->where('name', 'Dinas Kesehatan')->update(['type' => 'opd', 'updated_at' => now()]);
      $this->hospitals('rumah_sakit')->update(['type' => 'opd', 'updated_at' => now()]);
    });
  }
};
