<?php

namespace Database\Seeders;

use App\Models\SppdWorkflow;
use Illuminate\Database\Seeder;

class SppdWorkflowSeeder extends Seeder
{
  public function run(): void
  {
    $workflows = [

      // ═══════════════════════════════════════════════════════════════
      // A. OPD (Dinas / Badan / Inspektorat)
      // ═══════════════════════════════════════════════════════════════

      // Staff OPD — Dalam Daerah: Kasubag → Sekretaris → Kepala OPD
      [
        'name'            => 'OPD Staff – Dalam Daerah',
        'department_type' => 'opd',
        'applicant_role'  => 'staff',
        'destination'     => ['dalam_daerah'],
        'steps'           => ['kasubag', 'sekretaris', 'kepala_opd'],
      ],
      // Staff OPD — LDDP: Kasubag → Sekretaris → Kepala OPD
      [
        'name'            => 'OPD Staff – LDDP',
        'department_type' => 'opd',
        'applicant_role'  => 'staff',
        'destination'     => ['lddp'],
        'steps'           => ['kasubag', 'sekretaris', 'kepala_opd'],
      ],
      // Staff OPD — LDLP: Kasubag → Sekretaris → Kepala OPD → Sekda
      [
        'name'            => 'OPD Staff – LDLP',
        'department_type' => 'opd',
        'applicant_role'  => 'staff',
        'destination'     => ['ldlp'],
        'steps'           => ['kasubag', 'sekretaris', 'kepala_opd', 'sekda'],
      ],

      // Kasubag OPD — Dalam Daerah: Sekretaris → Kepala OPD
      [
        'name'            => 'OPD Kasubag – Dalam Daerah',
        'department_type' => 'opd',
        'applicant_role'  => 'kasubag',
        'destination'     => ['dalam_daerah'],
        'steps'           => ['sekretaris', 'kepala_opd'],
      ],
      // Kasubag OPD — LDDP: Sekretaris → Kepala OPD
      [
        'name'            => 'OPD Kasubag – LDDP',
        'department_type' => 'opd',
        'applicant_role'  => 'kasubag',
        'destination'     => ['lddp'],
        'steps'           => ['sekretaris', 'kepala_opd'],
      ],
      // Kasubag OPD — LDLP: Sekretaris → Kepala OPD → Sekda
      [
        'name'            => 'OPD Kasubag – LDLP',
        'department_type' => 'opd',
        'applicant_role'  => 'kasubag',
        'destination'     => ['ldlp'],
        'steps'           => ['sekretaris', 'kepala_opd', 'sekda'],
      ],

      // Sekretaris OPD — Dalam Daerah & LDDP: Kepala OPD
      [
        'name'            => 'OPD Sekretaris – Dalam Daerah/LDDP',
        'department_type' => 'opd',
        'applicant_role'  => 'sekretaris',
        'destination'     => ['dalam_daerah', 'lddp'],
        'steps'           => ['kepala_opd'],
      ],
      // Sekretaris OPD — LDLP: Kepala OPD → Sekda
      [
        'name'            => 'OPD Sekretaris – LDLP',
        'department_type' => 'opd',
        'applicant_role'  => 'sekretaris',
        'destination'     => ['ldlp'],
        'steps'           => ['kepala_opd', 'sekda'],
      ],

      // Kepala OPD — Dalam Daerah & LDDP: Sekda
      [
        'name'            => 'OPD Kepala – Dalam Daerah/LDDP',
        'department_type' => 'opd',
        'applicant_role'  => 'kepala_opd',
        'destination'     => ['dalam_daerah', 'lddp'],
        'steps'           => ['sekda'],
      ],
      // Kepala OPD — LDLP: Sekda → Walikota
      [
        'name'            => 'OPD Kepala – LDLP',
        'department_type' => 'opd',
        'applicant_role'  => 'kepala_opd',
        'destination'     => ['ldlp'],
        'steps'           => ['sekda', 'walikota'],
      ],

      // ═══════════════════════════════════════════════════════════════
      // B. DINAS KESEHATAN (khusus karena punya UPTD Puskesmas)
      // ═══════════════════════════════════════════════════════════════

      // Staff Dinkes — Dalam Daerah: Kasubag → Sekretaris → Kepala OPD
      [
        'name'            => 'Dinkes Staff – Dalam Daerah',
        'department_type' => 'dinkes',
        'applicant_role'  => 'staff',
        'destination'     => ['dalam_daerah'],
        'steps'           => ['kasubag', 'sekretaris', 'kepala_opd'],
      ],
      // Staff Dinkes — LDDP: Kasubag → Sekretaris → Kepala OPD
      [
        'name'            => 'Dinkes Staff – LDDP',
        'department_type' => 'dinkes',
        'applicant_role'  => 'staff',
        'destination'     => ['lddp'],
        'steps'           => ['kasubag', 'sekretaris', 'kepala_opd'],
      ],
      // Staff Dinkes — LDLP: Kasubag → Sekretaris → Kepala OPD → Sekda
      [
        'name'            => 'Dinkes Staff – LDLP',
        'department_type' => 'dinkes',
        'applicant_role'  => 'staff',
        'destination'     => ['ldlp'],
        'steps'           => ['kasubag', 'sekretaris', 'kepala_opd', 'sekda'],
      ],

      // ═══════════════════════════════════════════════════════════════
      // C. PUSKESMAS (UPTD di bawah Dinkes)
      // ═══════════════════════════════════════════════════════════════

      // Staff Puskesmas — Dalam Daerah: Kapus → Kepala OPD (Dinkes)
      [
        'name'            => 'Puskesmas Staff – Dalam Daerah',
        'department_type' => 'puskesmas',
        'applicant_role'  => 'staff',
        'destination'     => ['dalam_daerah'],
        'steps'           => ['kapus', 'kepala_opd'],
      ],
      // Staff Puskesmas — LDDP: Kapus → Kepala OPD (Dinkes)
      [
        'name'            => 'Puskesmas Staff – LDDP',
        'department_type' => 'puskesmas',
        'applicant_role'  => 'staff',
        'destination'     => ['lddp'],
        'steps'           => ['kapus', 'kepala_opd'],
      ],
      // Staff Puskesmas — LDLP: Kapus → Kepala OPD (Dinkes) → Sekda
      [
        'name'            => 'Puskesmas Staff – LDLP',
        'department_type' => 'puskesmas',
        'applicant_role'  => 'staff',
        'destination'     => ['ldlp'],
        'steps'           => ['kapus', 'kepala_opd', 'sekda'],
      ],

      // Kapus — Dalam Daerah & LDDP: Kepala OPD (Dinkes)
      [
        'name'            => 'Puskesmas Kapus – Dalam Daerah/LDDP',
        'department_type' => 'puskesmas',
        'applicant_role'  => 'kapus',
        'destination'     => ['dalam_daerah', 'lddp'],
        'steps'           => ['kepala_opd'],
      ],
      // Kapus — LDLP: Kepala OPD (Dinkes) → Sekda
      [
        'name'            => 'Puskesmas Kapus – LDLP',
        'department_type' => 'puskesmas',
        'applicant_role'  => 'kapus',
        'destination'     => ['ldlp'],
        'steps'           => ['kepala_opd', 'sekda'],
      ],

      // ═══════════════════════════════════════════════════════════════
      // D. SEKRETARIAT DAERAH (Setda) & Bagian di bawahnya
      // ═══════════════════════════════════════════════════════════════

      // Staff Bagian Setda — Dalam Daerah: Kabag → Sekda
      [
        'name'            => 'Bagian Setda Staff – Dalam Daerah',
        'department_type' => 'bagian',
        'applicant_role'  => 'staff',
        'destination'     => ['dalam_daerah'],
        'steps'           => ['kabag', 'sekda'],
      ],
      // Staff Bagian Setda — LDDP: Kabag → Sekda
      [
        'name'            => 'Bagian Setda Staff – LDDP',
        'department_type' => 'bagian',
        'applicant_role'  => 'staff',
        'destination'     => ['lddp'],
        'steps'           => ['kabag', 'sekda'],
      ],
      // Staff Bagian Setda — LDLP: Kabag → Sekda → Walikota
      [
        'name'            => 'Bagian Setda Staff – LDLP',
        'department_type' => 'bagian',
        'applicant_role'  => 'staff',
        'destination'     => ['ldlp'],
        'steps'           => ['kabag', 'sekda', 'walikota'],
      ],

      // Kabag — Dalam Daerah & LDDP: Sekda
      [
        'name'            => 'Bagian Setda Kabag – Dalam Daerah/LDDP',
        'department_type' => 'bagian',
        'applicant_role'  => 'kabag',
        'destination'     => ['dalam_daerah', 'lddp'],
        'steps'           => ['sekda'],
      ],
      // Kabag — LDLP: Sekda → Walikota
      [
        'name'            => 'Bagian Setda Kabag – LDLP',
        'department_type' => 'bagian',
        'applicant_role'  => 'kabag',
        'destination'     => ['ldlp'],
        'steps'           => ['sekda', 'walikota'],
      ],

      // ═══════════════════════════════════════════════════════════════
      // E. KECAMATAN
      // ═══════════════════════════════════════════════════════════════

      // Staff Kecamatan — Dalam Daerah: Camat
      [
        'name'            => 'Kecamatan Staff – Dalam Daerah',
        'department_type' => 'kecamatan',
        'applicant_role'  => 'staff',
        'destination'     => ['dalam_daerah'],
        'steps'           => ['camat'],
      ],
      // Staff Kecamatan — LDDP: Camat → Sekda
      [
        'name'            => 'Kecamatan Staff – LDDP',
        'department_type' => 'kecamatan',
        'applicant_role'  => 'staff',
        'destination'     => ['lddp'],
        'steps'           => ['camat', 'sekda'],
      ],
      // Staff Kecamatan — LDLP: Camat → Sekda → Walikota
      [
        'name'            => 'Kecamatan Staff – LDLP',
        'department_type' => 'kecamatan',
        'applicant_role'  => 'staff',
        'destination'     => ['ldlp'],
        'steps'           => ['camat', 'sekda', 'walikota'],
      ],

      // Camat — Dalam Daerah & LDDP: Sekda
      [
        'name'            => 'Kecamatan Camat – Dalam Daerah/LDDP',
        'department_type' => 'kecamatan',
        'applicant_role'  => 'camat',
        'destination'     => ['dalam_daerah', 'lddp'],
        'steps'           => ['sekda'],
      ],
      // Camat — LDLP: Sekda → Walikota
      [
        'name'            => 'Kecamatan Camat – LDLP',
        'department_type' => 'kecamatan',
        'applicant_role'  => 'camat',
        'destination'     => ['ldlp'],
        'steps'           => ['sekda', 'walikota'],
      ],

      // ═══════════════════════════════════════════════════════════════
      // F. KELURAHAN
      // ═══════════════════════════════════════════════════════════════

      // Staff Kelurahan — Dalam Daerah: Lurah → Camat
      [
        'name'            => 'Kelurahan Staff – Dalam Daerah',
        'department_type' => 'kelurahan',
        'applicant_role'  => 'staff',
        'destination'     => ['dalam_daerah'],
        'steps'           => ['lurah', 'camat'],
      ],
      // Staff Kelurahan — LDDP: Lurah → Camat → Sekda
      [
        'name'            => 'Kelurahan Staff – LDDP',
        'department_type' => 'kelurahan',
        'applicant_role'  => 'staff',
        'destination'     => ['lddp'],
        'steps'           => ['lurah', 'camat', 'sekda'],
      ],
      // Staff Kelurahan — LDLP: Lurah → Camat → Sekda → Walikota
      [
        'name'            => 'Kelurahan Staff – LDLP',
        'department_type' => 'kelurahan',
        'applicant_role'  => 'staff',
        'destination'     => ['ldlp'],
        'steps'           => ['lurah', 'camat', 'sekda', 'walikota'],
      ],

      // Lurah — Dalam Daerah: Camat
      [
        'name'            => 'Kelurahan Lurah – Dalam Daerah',
        'department_type' => 'kelurahan',
        'applicant_role'  => 'lurah',
        'destination'     => ['dalam_daerah'],
        'steps'           => ['camat'],
      ],
      // Lurah — LDDP: Camat → Sekda
      [
        'name'            => 'Kelurahan Lurah – LDDP',
        'department_type' => 'kelurahan',
        'applicant_role'  => 'lurah',
        'destination'     => ['lddp'],
        'steps'           => ['camat', 'sekda'],
      ],
      // Lurah — LDLP: Camat → Sekda → Walikota
      [
        'name'            => 'Kelurahan Lurah – LDLP',
        'department_type' => 'kelurahan',
        'applicant_role'  => 'lurah',
        'destination'     => ['ldlp'],
        'steps'           => ['camat', 'sekda', 'walikota'],
      ],

      // ═══════════════════════════════════════════════════════════════
      // G. DPRD (Sekretariat DPRD)
      // ═══════════════════════════════════════════════════════════════

      // Staff DPRD — Dalam Daerah: Sekwan
      [
        'name'            => 'DPRD Staff – Dalam Daerah',
        'department_type' => 'dprd',
        'applicant_role'  => 'staff_dprd',
        'destination'     => ['dalam_daerah'],
        'steps'           => ['sekwan'],
      ],
      // Staff DPRD — LDDP: Sekwan → Pimpinan DPRD
      [
        'name'            => 'DPRD Staff – LDDP',
        'department_type' => 'dprd',
        'applicant_role'  => 'staff_dprd',
        'destination'     => ['lddp'],
        'steps'           => ['sekwan', 'pimpinan_dprd'],
      ],
      // Staff DPRD — LDLP: Sekwan → Pimpinan DPRD
      [
        'name'            => 'DPRD Staff – LDLP',
        'department_type' => 'dprd',
        'applicant_role'  => 'staff_dprd',
        'destination'     => ['ldlp'],
        'steps'           => ['sekwan', 'pimpinan_dprd'],
      ],

      // Anggota DPRD — Dalam Daerah & LDDP: Sekwan → Pimpinan DPRD
      [
        'name'            => 'DPRD Anggota – Dalam Daerah/LDDP',
        'department_type' => 'dprd',
        'applicant_role'  => 'anggota_dprd',
        'destination'     => ['dalam_daerah', 'lddp'],
        'steps'           => ['sekwan', 'pimpinan_dprd'],
      ],
      // Anggota DPRD — LDLP: Sekwan → Pimpinan DPRD
      [
        'name'            => 'DPRD Anggota – LDLP',
        'department_type' => 'dprd',
        'applicant_role'  => 'anggota_dprd',
        'destination'     => ['ldlp'],
        'steps'           => ['sekwan', 'pimpinan_dprd'],
      ],

      // Sekwan — LDLP: Pimpinan DPRD
      [
        'name'            => 'DPRD Sekwan – LDLP',
        'department_type' => 'dprd',
        'applicant_role'  => 'sekwan',
        'destination'     => ['ldlp'],
        'steps'           => ['pimpinan_dprd'],
      ],

      // ═══════════════════════════════════════════════════════════════
      // H. SEKDA sendiri — LDLP: Walikota
      // ═══════════════════════════════════════════════════════════════
      [
        'name'            => 'Sekda – LDLP',
        'department_type' => 'setda',
        'applicant_role'  => 'sekda',
        'destination'     => ['ldlp'],
        'steps'           => ['walikota'],
      ],
    ];

    foreach ($workflows as $wf) {
      SppdWorkflow::updateOrCreate(
        ['name' => $wf['name']],
        $wf
      );
    }
  }
}
