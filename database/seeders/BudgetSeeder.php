<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Department;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
  public function run(): void
  {
    $year = (int) date('Y');

    $programs = [
      [
        'program'  => 'Program Penunjang Urusan Pemerintahan Daerah Kabupaten/Kota',
        'kegiatan' => 'Administrasi Kepegawaian Perangkat Daerah',
        'kode'     => '1.01.01.2.02.01',
        'uraian'   => 'Biaya Perjalanan Dinas Dalam Daerah',
        'type'     => 'Perjalanan Dinas Dalam Daerah',
        'source'   => 'APBD'
      ],
      [
        'program'  => 'Program Peningkatan Kapasitas Sumber Daya Aparatur',
        'kegiatan' => 'Peningkatan Kompetensi Sumber Daya Aparatur',
        'kode'     => '1.01.01.2.06.01',
        'uraian'   => 'Pendidikan dan pelatihan formal',
        'type'     => 'Perjalanan Dinas Dalam Daerah',
        'source'   => 'APBD'
      ],
    ];

    foreach ($programs as $p) {
      Budget::updateOrCreate(
        [
          'department_id' => 36, // kominfo
          'account_code'  => $p['kode'],
          'year'          => $year,
          'type'          => $p['type'],
          'source'        => $p['source']
        ],
        [
          'program'      => $p['program'],
          'activity'     => $p['kegiatan'],
          'description'  => $p['uraian'],
          'total_amount' => rand(50, 500) * 1_000_000
        ]
      );
    }
  }
}
