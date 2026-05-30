<?php

namespace Database\Seeders;

use App\Models\SppdWorkflow;
use Illuminate\Database\Seeder;

class SppdWorkflowSeeder extends Seeder
{
  public function run(): void
  {
    $workflows = [
      // Anggota DPRD
      [
        'name'            => 'Anggota DPRD',
        'department_type' => ['dprd'],
        'applicant_role'  => ['anggota_dprd', 'pimpinan_dprd'],
        'destination'     => ['dalam_daerah', 'lddp', 'ldlp'],
        'steps'           => ['sekwan', 'pimpinan_dprd'],
      ],

      // Staff DPRD
      [
        'name'            => 'Staff DPRD',
        'department_type' => ['dprd'],
        'applicant_role'  => ['staf'],
        'destination'     => ['dalam_daerah', 'lddp', 'ldlp'],
        'steps'           => ['kabid_irban_kabag', 'sekwan'],
      ],

      // Sekwan
      [
        'name'            => 'Sekwan',
        'department_type' => ['dprd'],
        'applicant_role'  => ['sekwan'],
        'destination'     => ['dalam_daerah'],
        'steps'           => ['sekwan', 'sekda'],
      ],
      [
        'name'            => 'Sekwan',
        'department_type' => ['dprd'],
        'applicant_role'  => ['sekwan'],
        'destination'     => ['lddp', 'ldlp'],
        'steps'           => ['sekwan', 'sekda', 'walikota'],
      ],

      // Kepala OPD
      [
        'name'            => 'Kepala OPD',
        'department_type' => ['opd'],
        'applicant_role'  => ['kepala_opd'],
        'destination'     => ['dalam_daerah'],
        'steps'           => ['sekretaris_opd', 'kepala_opd'],
      ],
      [
        'name'            => 'Kepala OPD',
        'department_type' => ['opd'],
        'applicant_role'  => ['kepala_opd'],
        'destination'     => ['lddp', 'ldlp'],
        'steps'           => ['sekretaris_opd', 'kepala_opd', 'sekda', 'walikota'],
      ],
    ];

    foreach ($workflows as $wf) {
      SppdWorkflow::create($wf);
    }
  }
}
