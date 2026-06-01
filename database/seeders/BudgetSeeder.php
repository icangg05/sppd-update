<?php

namespace Database\Seeders;

use App\Models\Budget;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
  public function run(): void
  {
    $year = (int) date('Y');

    // PEMERINTAH KOTA
    $programPemerintahKota = [
      [
        'program'  => 'ADMINISTRASIAN TATA PEMERINTAHAN',
        'kegiatan' => 'PENATAAN ADMINISTRASI PEMERINTAHAN 2026',
        'kode'     => '5.1.02.04.001.00001',
        'uraian'   => 'Pelaksanaan Reses',
        'type'     => 'Perjalanan Dinas Luar Daerah',
        'source'   => 'APBD'
      ],
    ];

    foreach ($programPemerintahKota as $p) {
      Budget::create([
        'department_id' => 45,
        'account_code'  => $p['kode'],
        'year'          => $year,
        'type'          => $p['type'],
        'source'        => $p['source'],
        'program'       => $p['program'],
        'activity'      => $p['kegiatan'],
        'description'   => $p['uraian'],
        'total_amount'  => rand(50, 500) * 1_000_000
      ]);
    }

    // SEKRETARIAT DAERAH
    $programSetda = [
      [
        'program'  => 'PROGRAM PEMERINTAHAN DAN KESEJAHTERAAN RAKYAT',
        'kegiatan' => 'ADMINISTRASI TATA PEMERINTAHAN',
        'kode'     => '5.1.02.04.01.0003',
        'uraian'   => 'Pengelolaan Administrasi Kewilayahan',
        'type'     => 'Perjalanan Dinas Dalam Daerah',
        'source'   => 'APBD'
      ],
    ];

    foreach ($programSetda as $p) {
      Budget::create([
        'department_id' => 3,
        'account_code'  => $p['kode'],
        'year'          => $year,
        'type'          => $p['type'],
        'source'        => $p['source'],
        'program'       => $p['program'],
        'activity'      => $p['kegiatan'],
        'description'   => $p['uraian'],
        'total_amount'  => rand(50, 500) * 1_000_000
      ]);
    }

    // Kominfo
    $programsKominfo = [
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

    foreach ($programsKominfo as $p) {
      Budget::create([
        'department_id' => 36,
        'account_code'  => $p['kode'],
        'year'          => $year,
        'type'          => $p['type'],
        'source'        => $p['source'],
        'program'       => $p['program'],
        'activity'      => $p['kegiatan'],
        'description'   => $p['uraian'],
        'total_amount'  => rand(50, 500) * 1_000_000
      ]);
    }


    // Inspektorat
    $progamInspektorat = [
      [
        'program'  => 'Sosialisasi Pencegahan Korupsi (IRVES)',
        'kegiatan' => 'Pendampingan, Asistensi dan Verifikasi Penegakan Integritas',
        'kode'     => '6.01.03.2.02.04',
        'uraian'   => 'Belanja Perjalanan Dinas Dalam Daerah',
        'type'     => 'Perjalanan Dinas Dalam Daerah',
        'source'   => 'APBD'
      ],
      [
        'program'  => 'Sosialisasi SPI',
        'kegiatan' => 'Pendampingan, Asistensi dan Verifikasi Penegakan Integritas ',
        'kode'     => '6.01.03.2.02.04',
        'uraian'   => 'Belanja Perjalanan Dinas Dalam Daerah',
        'type'     => 'Perjalanan Dinas Dalam Daerah',
        'source'   => 'APBD'
      ],
    ];

    foreach ($progamInspektorat as $p) {
      Budget::create([
        'department_id' => 48,
        'account_code'  => $p['kode'],
        'year'          => $year,
        'type'          => $p['type'],
        'source'        => $p['source'],
        'program'       => $p['program'],
        'activity'      => $p['kegiatan'],
        'description'   => $p['uraian'],
        'total_amount'  => rand(50, 500) * 1_000_000
      ]);
    }


    // DPRD
    $programDprd = [
      [
        'program'  => 'Program Dukungan Pelaksanaan Tugas dan Fungsi DPRD',
        'kegiatan' => 'Penyerapan dan Penghimpunan Aspirasi Masyarakat',
        'kode'     => '5.1.02.04.01.0003',
        'uraian'   => 'Pelaksanaan Reses',
        'type'     => 'Perjalanan Dinas Dalam Daerah',
        'source'   => 'APBD'
      ],
    ];

    foreach ($programDprd as $p) {
      Budget::create([
        'department_id' => 2,
        'account_code'  => $p['kode'],
        'year'          => $year,
        'type'          => $p['type'],
        'source'        => $p['source'],
        'program'       => $p['program'],
        'activity'      => $p['kegiatan'],
        'description'   => $p['uraian'],
        'total_amount'  => rand(50, 500) * 1_000_000
      ]);
    }
  }
}
