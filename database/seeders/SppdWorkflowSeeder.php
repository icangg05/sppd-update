<?php

namespace Database\Seeders;

use App\Models\SppdWorkflow;
use Illuminate\Database\Seeder;

/**
 * Alur persetujuan SPPD Pemerintah Kota Kendari.
 *
 * Setiap kombinasi tipe instansi × peran pemohon × domain perjalanan × atasan
 * langsung pemohon yang sah dicakup tepat satu alur (dijaga oleh
 * Tests\Feature\SppdWorkflowCoverageTest). Setiap tahap alur wajib dijalankan.
 *
 * Pola penandatangan (mengikuti pola umum Perkada perjalanan dinas):
 *  - SPT pegawai (eselon III ke bawah) ditandatangani Kepala Perangkat Daerah;
 *    SPPD oleh Pengguna Anggaran unitnya (Kepala OPD/Camat/Lurah/Kapus, Sekda, Sekwan).
 *  - SPT Kepala Perangkat Daerah & Sekwan ke luar daerah, serta SPT seluruh
 *    perjalanan luar negeri, ditandatangani Wali Kota setelah diverifikasi Sekda.
 *  - SPT Pimpinan & Anggota DPRD ditandatangani Pimpinan DPRD.
 *  - Tingkat persetujuan terendah Kabid/Irban/Kabag. Staf yang atasan langsungnya
 *    bukan Kabid (mis. staf Sekretariat di bawah Sekretaris) memakai alur tanpa
 *    tahap Kabid: alur dipecah per atasan langsung (lihat byAtasan).
 */
class SppdWorkflowSeeder extends Seeder
{
  // Tipe instansi
  private const OPD       = ['opd', 'dinkes', 'sekretariat'];
  private const WILAYAH   = ['kecamatan', 'kelurahan'];
  private const PUSKESMAS = ['puskesmas'];
  private const SETDA     = ['setda', 'asisten', 'bagian', 'subbagian'];
  private const DPRD      = ['dprd'];

  /** Penanda tahap Kabid/Irban/Kabag: alurnya dipecah per atasan langsung pemohon. */
  private const KABID = '__kabid__';

  /** Label role untuk nama alur (sama dengan label di tabel roles). */
  private const LABELS = [
    'kabid_irban_kabag' => 'Kabid / Irban / Kabag',
    'sekretaris_opd'    => 'Sekretaris OPD',
    'asisten'           => 'Asisten',
    'sekwan'            => 'Sekwan',
  ];

  // Domain perjalanan
  private const DALAM_DAERAH = ['dalam_daerah'];
  private const LUAR_DAERAH  = ['lddp', 'ldlp'];
  private const NON_LN       = ['dalam_daerah', 'lddp', 'ldlp'];
  private const LUAR_NEGERI  = ['luar_negeri'];
  private const SEMUA        = ['dalam_daerah', 'lddp', 'ldlp', 'luar_negeri'];

  public function run(): void
  {
    SppdWorkflow::truncate();

    foreach (self::workflows() as $wf) {
      SppdWorkflow::create($wf);
    }
  }

  /** @return array<int, array<string, mixed>> */
  public static function workflows(): array
  {
    $kabid = self::KABID;

    // Verifikasi Sekda lalu SPT oleh Wali Kota (luar daerah pimpinan unit & seluruh luar negeri).
    $keWalikota = [self::step('sekda'), self::step('walikota', spt: true)];

    $workflows = [
      // ── OPD, Dinas Kesehatan & Sekretariat ──────────────────────────────
      [
        'name'            => 'Staf & Kasubag/Kasubid OPD',
        'department_type' => self::OPD,
        'applicant_role'  => ['staf', 'kasubid_kasubag'],
        'destination'     => self::NON_LN,
        'steps'           => [$kabid, self::step('sekretaris_opd'), self::step('kepala_opd', spt: true, sppd: true)],
      ],
      [
        'name'            => 'Staf & Kasubag/Kasubid OPD — Luar Negeri',
        'department_type' => self::OPD,
        'applicant_role'  => ['staf', 'kasubid_kasubag'],
        'destination'     => self::LUAR_NEGERI,
        'steps'           => [$kabid, self::step('sekretaris_opd'), self::step('kepala_opd', sppd: true), ...$keWalikota],
      ],
      [
        'name'            => 'Kabid / Irban OPD',
        'department_type' => self::OPD,
        'applicant_role'  => ['kabid_irban_kabag'],
        'destination'     => self::NON_LN,
        'steps'           => [self::step('sekretaris_opd'), self::step('kepala_opd', spt: true, sppd: true)],
      ],
      [
        'name'            => 'Kabid / Irban OPD — Luar Negeri',
        'department_type' => self::OPD,
        'applicant_role'  => ['kabid_irban_kabag'],
        'destination'     => self::LUAR_NEGERI,
        'steps'           => [self::step('sekretaris_opd'), self::step('kepala_opd', sppd: true), ...$keWalikota],
      ],

      // ── Kecamatan & Kelurahan (tanpa jenjang Kabid) ─────────────────────
      [
        'name'            => 'Staf & Kasi Kecamatan/Kelurahan',
        'department_type' => self::WILAYAH,
        'applicant_role'  => ['staf', 'kasubid_kasubag'],
        'destination'     => self::NON_LN,
        'steps'           => [self::step('sekretaris_opd'), self::step('kepala_opd', spt: true, sppd: true)],
      ],
      [
        'name'            => 'Staf & Kasi Kecamatan/Kelurahan — Luar Negeri',
        'department_type' => self::WILAYAH,
        'applicant_role'  => ['staf', 'kasubid_kasubag'],
        'destination'     => self::LUAR_NEGERI,
        'steps'           => [self::step('sekretaris_opd'), self::step('kepala_opd', sppd: true), ...$keWalikota],
      ],

      // ── Sekretaris & Kepala unit (OPD, Kecamatan, Kelurahan) ────────────
      [
        'name'            => 'Sekretaris OPD, Sekcam & Sekretaris Lurah',
        'department_type' => [...self::OPD, ...self::WILAYAH],
        'applicant_role'  => ['sekretaris_opd', 'sekcam'],
        'destination'     => self::NON_LN,
        'steps'           => [self::step('kepala_opd', spt: true, sppd: true)],
      ],
      [
        'name'            => 'Sekretaris OPD, Sekcam & Sekretaris Lurah — Luar Negeri',
        'department_type' => [...self::OPD, ...self::WILAYAH],
        'applicant_role'  => ['sekretaris_opd', 'sekcam'],
        'destination'     => self::LUAR_NEGERI,
        'steps'           => [self::step('kepala_opd', sppd: true), ...$keWalikota],
      ],
      [
        'name'            => 'Kepala OPD, Camat & Lurah — Dalam Daerah',
        'department_type' => [...self::OPD, ...self::WILAYAH],
        'applicant_role'  => ['kepala_opd', 'camat', 'lurah'],
        'destination'     => self::DALAM_DAERAH,
        'steps'           => [self::step('sekretaris_opd'), self::step('kepala_opd', spt: true, sppd: true)],
      ],
      [
        'name'            => 'Kepala OPD, Camat & Lurah — Luar Daerah & Luar Negeri',
        'department_type' => [...self::OPD, ...self::WILAYAH],
        'applicant_role'  => ['kepala_opd', 'camat', 'lurah'],
        'destination'     => [...self::LUAR_DAERAH, ...self::LUAR_NEGERI],
        'steps'           => [self::step('sekretaris_opd'), self::step('kepala_opd', sppd: true), ...$keWalikota],
      ],

      // ── Puskesmas (tanpa Sekretaris; Kasubag TU bukan penyetuju) ────────
      [
        'name'            => 'Staf & Kasubag TU Puskesmas',
        'department_type' => self::PUSKESMAS,
        'applicant_role'  => ['staf', 'kasubid_kasubag'],
        'destination'     => self::NON_LN,
        'steps'           => [self::step('kepala_opd', spt: true, sppd: true)],
      ],
      [
        'name'            => 'Staf & Kasubag TU Puskesmas — Luar Negeri',
        'department_type' => self::PUSKESMAS,
        'applicant_role'  => ['staf', 'kasubid_kasubag'],
        'destination'     => self::LUAR_NEGERI,
        'steps'           => [self::step('kepala_opd', sppd: true), ...$keWalikota],
      ],
      [
        'name'            => 'Kepala Puskesmas — Dalam Daerah',
        'department_type' => self::PUSKESMAS,
        'applicant_role'  => ['kepala_opd', 'kapus'],
        'destination'     => self::DALAM_DAERAH,
        'steps'           => [self::step('kepala_opd', spt: true, sppd: true)],
      ],
      [
        'name'            => 'Kepala Puskesmas — Luar Daerah & Luar Negeri',
        'department_type' => self::PUSKESMAS,
        'applicant_role'  => ['kepala_opd', 'kapus'],
        'destination'     => [...self::LUAR_DAERAH, ...self::LUAR_NEGERI],
        'steps'           => [self::step('kepala_opd', sppd: true), ...$keWalikota],
      ],

      // ── Sekretariat Daerah ──────────────────────────────────────────────
      [
        'name'            => 'Staf & Kasubag Setda',
        'department_type' => self::SETDA,
        'applicant_role'  => ['staf', 'kasubid_kasubag'],
        'destination'     => self::NON_LN,
        'steps'           => [$kabid, self::step('asisten'), self::step('sekda', spt: true, sppd: true)],
      ],
      [
        'name'            => 'Staf & Kasubag Setda — Luar Negeri',
        'department_type' => self::SETDA,
        'applicant_role'  => ['staf', 'kasubid_kasubag'],
        'destination'     => self::LUAR_NEGERI,
        'steps'           => [$kabid, self::step('asisten'), self::step('sekda', sppd: true), self::step('walikota', spt: true)],
      ],
      [
        'name'            => 'Kabag Setda',
        'department_type' => self::SETDA,
        'applicant_role'  => ['kabid_irban_kabag'],
        'destination'     => self::NON_LN,
        'steps'           => [self::step('asisten'), self::step('sekda', spt: true, sppd: true)],
      ],
      [
        'name'            => 'Kabag Setda — Luar Negeri',
        'department_type' => self::SETDA,
        'applicant_role'  => ['kabid_irban_kabag'],
        'destination'     => self::LUAR_NEGERI,
        'steps'           => [self::step('asisten'), self::step('sekda', sppd: true), self::step('walikota', spt: true)],
      ],
      [
        'name'            => 'Wali Kota, Wakil Wali Kota, Sekda & Asisten',
        'department_type' => self::SETDA,
        'applicant_role'  => ['walikota', 'wakil_walikota', 'sekda', 'asisten'],
        'destination'     => self::SEMUA,
        'steps'           => [self::step('sekda', sppd: true), self::step('walikota', spt: true)],
      ],

      // ── DPRD & Sekretariat DPRD ─────────────────────────────────────────
      [
        'name'            => 'Pimpinan & Anggota DPRD',
        'department_type' => self::DPRD,
        'applicant_role'  => ['anggota_dprd', 'pimpinan_dprd'],
        'destination'     => self::SEMUA,
        'steps'           => [self::step('sekwan', sppd: true), self::step('pimpinan_dprd', spt: true)],
      ],
      [
        'name'            => 'Staf & Kasubag Sekretariat DPRD',
        'department_type' => self::DPRD,
        'applicant_role'  => ['staf', 'kasubid_kasubag'],
        'destination'     => self::NON_LN,
        'steps'           => [$kabid, self::step('sekwan', spt: true, sppd: true)],
      ],
      [
        'name'            => 'Staf & Kasubag Sekretariat DPRD — Luar Negeri',
        'department_type' => self::DPRD,
        'applicant_role'  => ['staf', 'kasubid_kasubag'],
        'destination'     => self::LUAR_NEGERI,
        'steps'           => [$kabid, self::step('sekwan', sppd: true), ...$keWalikota],
      ],
      [
        'name'            => 'Kabag Sekretariat DPRD',
        'department_type' => self::DPRD,
        'applicant_role'  => ['kabid_irban_kabag'],
        'destination'     => self::NON_LN,
        'steps'           => [self::step('sekwan', spt: true, sppd: true)],
      ],
      [
        'name'            => 'Kabag Sekretariat DPRD — Luar Negeri',
        'department_type' => self::DPRD,
        'applicant_role'  => ['kabid_irban_kabag'],
        'destination'     => self::LUAR_NEGERI,
        'steps'           => [self::step('sekwan', sppd: true), ...$keWalikota],
      ],
      [
        'name'            => 'Sekwan — Dalam Daerah',
        'department_type' => self::DPRD,
        'applicant_role'  => ['sekwan'],
        'destination'     => self::DALAM_DAERAH,
        'steps'           => [self::step('sekwan', sppd: true), self::step('sekda', spt: true)],
      ],
      [
        'name'            => 'Sekwan — Luar Daerah & Luar Negeri',
        'department_type' => self::DPRD,
        'applicant_role'  => ['sekwan'],
        'destination'     => [...self::LUAR_DAERAH, ...self::LUAR_NEGERI],
        'steps'           => [self::step('sekwan', sppd: true), ...$keWalikota],
      ],
    ];

    return array_map(fn (array $wf) => $wf + ['supervisor_role' => null, 'is_active' => true], self::byAtasan($workflows));
  }

  /**
   * Alur bertahap Kabid dipecah dua menurut atasan langsung pemohon: beratasan Kabid
   * melewati tahap Kabid; beratasan pejabat tahap sesudahnya mulai dari pejabat itu.
   */
  private static function byAtasan(array $workflows): array
  {
    $result = [];
    foreach ($workflows as $wf) {
      $at = array_search(self::KABID, $wf['steps'], true);
      if ($at === false) {
        $result[] = $wf;
        continue;
      }

      $next = $wf['steps'][$at + 1]['role'];
      $withKabid = $wf['steps'];
      $withKabid[$at] = self::step('kabid_irban_kabag');
      $withoutKabid = $wf['steps'];
      unset($withoutKabid[$at]);

      $result[] = ['name' => $wf['name'] . ' (atasan ' . self::LABELS['kabid_irban_kabag'] . ')', 'steps' => $withKabid, 'supervisor_role' => ['kabid_irban_kabag']] + $wf;
      $result[] = ['name' => $wf['name'] . ' (atasan ' . self::LABELS[$next] . ')', 'steps' => array_values($withoutKabid), 'supervisor_role' => [$next]] + $wf;
    }

    return $result;
  }

  private static function step(string $role, bool $spt = false, bool $sppd = false): array
  {
    return ['role' => $role, 'signs_spt' => $spt, 'signs_sppd' => $sppd];
  }
}
