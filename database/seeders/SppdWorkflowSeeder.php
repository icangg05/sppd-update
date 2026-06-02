<?php

namespace Database\Seeders;

use App\Models\SppdWorkflow;
use Illuminate\Database\Seeder;

class SppdWorkflowSeeder extends Seeder
{
  public function run(): void
  {
    $workflows = [
      // Walikota, Wakil Walikota & Asisten
      [
        'name'            => 'Walikota, Wakil Walikota & Asisten',
        'department_type' => ['setda', 'asisten'],
        'applicant_role'  => ['walikota', 'wakil_walikota', 'asisten'],
        'destination'     => ['dalam_daerah', 'lddp', 'ldlp'],
        'steps'           => [
          ['role' => 'sekda', 'signs_spt' => false, 'signs_sppd' => true],
          ['role' => 'walikota', 'signs_spt' => true, 'signs_sppd' => false]
        ],
      ],

      // Kasubag & Staff (Setda)
      [
        'name'            => 'Kasubag & Staf (Setda)',
        'department_type' => ['setda', 'bagian', 'subbagian'],
        'applicant_role'  => ['kabid_irban_kabag', 'kasubid_kasubag', 'staf'],
        'destination'     => ['dalam_daerah', 'lddp', 'ldlp'],
        'steps'           => [
          ['role' => 'kabid_irban_kabag', 'signs_spt' => false, 'signs_sppd' => false],
          ['role' => 'asisten', 'signs_spt' => false, 'signs_sppd' => false],
          ['role' => 'sekda', 'signs_spt' => true, 'signs_sppd' => true],
        ],
      ],

      // Anggota DPRD
      [
        'name'            => 'Anggota DPRD',
        'department_type' => ['dprd'],
        'applicant_role'  => ['anggota_dprd', 'pimpinan_dprd'],
        'destination'     => ['dalam_daerah', 'lddp', 'ldlp'],
        'steps'           => [
          ['role' => 'sekwan', 'signs_spt' => false, 'signs_sppd' => true],
          ['role' => 'pimpinan_dprd', 'signs_spt' => true, 'signs_sppd' => false]
        ],
      ],

      // Staff DPRD
      [
        'name'            => 'Staff DPRD',
        'department_type' => ['dprd'],
        'applicant_role'  => ['staf'],
        'destination'     => ['dalam_daerah', 'lddp', 'ldlp'],
        'steps'           => [
          ['role' => 'kabid_irban_kabag', 'signs_spt' => false, 'signs_sppd' => false],
          ['role' => 'sekwan', 'signs_spt' => true, 'signs_sppd' => true]
        ],
      ],

      // Sekwan
      [
        'name'            => 'Sekwan',
        'department_type' => ['dprd'],
        'applicant_role'  => ['sekwan'],
        'destination'     => ['dalam_daerah'],
        'steps'           => [
          ['role' => 'sekwan', 'signs_spt' => false, 'signs_sppd' => true],
          ['role' => 'sekda', 'signs_spt' => true, 'signs_sppd' => false]
        ],
      ],
      [
        'name'            => 'Sekwan',
        'department_type' => ['dprd'],
        'applicant_role'  => ['sekwan'],
        'destination'     => ['lddp', 'ldlp'],
        'steps'           => [
          ['role' => 'sekwan', 'signs_spt' => false, 'signs_sppd' => true],
          ['role' => 'sekda', 'signs_spt' => false, 'signs_sppd' => false],
          ['role' => 'walikota', 'signs_spt' => true, 'signs_sppd' => false]
        ],
      ],

      // Kepala OPD
      [
        'name'            => 'Kepala OPD',
        'department_type' => ['opd', 'dinkes'],
        'applicant_role'  => ['kepala_opd'],
        'destination'     => ['dalam_daerah'],
        'steps'           => [
          ['role' => 'sekretaris_opd', 'signs_spt' => false, 'signs_sppd' => false],
          ['role' => 'kepala_opd', 'signs_spt' => true, 'signs_sppd' => true]
        ],
      ],
      [
        'name'            => 'Kepala OPD',
        'department_type' => ['opd', 'dinkes'],
        'applicant_role'  => ['kepala_opd'],
        'destination'     => ['lddp', 'ldlp'],
        'steps'           => [
          ['role' => 'sekretaris_opd', 'signs_spt' => false, 'signs_sppd' => false],
          ['role' => 'kepala_opd', 'signs_spt' => false, 'signs_sppd' => true],
          ['role' => 'sekda', 'signs_spt' => false, 'signs_sppd' => false],
          ['role' => 'walikota', 'signs_spt' => true, 'signs_sppd' => false]
        ],
      ],
    ];

    SppdWorkflow::truncate();

    foreach ($workflows as $wf) {
      SppdWorkflow::create($wf);
    }
  }
}
