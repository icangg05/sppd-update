<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Rank;
use Illuminate\Database\Seeder;

class PositionAndRankSeeder extends Seeder
{
  public function run(): void
  {
    // ── Golongan / Pangkat ──
    $ranks = [
      ['name' => 'Juru Muda', 'group' => 'I/a'],
      ['name' => 'Juru Muda Tingkat I', 'group' => 'I/b'],
      ['name' => 'Juru', 'group' => 'I/c'],
      ['name' => 'Juru Tingkat I', 'group' => 'I/d'],
      ['name' => 'Pengatur Muda', 'group' => 'II/a'],
      ['name' => 'Pengatur Muda Tingkat I', 'group' => 'II/b'],
      ['name' => 'Pengatur', 'group' => 'II/c'],
      ['name' => 'Pengatur Tingkat 1', 'group' => 'II/d'],
      ['name' => 'Penata Muda', 'group' => 'III/a'],
      ['name' => 'Penata Muda Tingkat 1', 'group' => 'III/b'],
      ['name' => 'Penata', 'group' => 'III/c'],
      ['name' => 'Penata Tingkat 1', 'group' => 'III/d'],
      ['name' => 'Pembina', 'group' => 'IV/a'],
      ['name' => 'Pembina Tingkat 1', 'group' => 'IV/b'],
      ['name' => 'Pembina Utama Muda', 'group' => 'IV/c'],
      ['name' => 'Pembina Utama Madya', 'group' => 'IV/d'],
      ['name' => 'Pembina Utama', 'group' => 'IV/e'],
    ];

    foreach ($ranks as $rank) {
      Rank::updateOrCreate(['group' => $rank['group']], $rank);
    }

    // ── Jabatan ──
    $positions = [
      'Kepala Dinas',
      'Sekretaris Dinas',
      'Kepala Bidang',
      'Kepala Seksi',
      'Kepala Sub Bagian',
      'Camat',
      'Sekretaris Camat',
      'Lurah',
      'Sekretaris Lurah',
      'Kepala Puskesmas',
      'Kepala Badan',
      'Inspektur',
      'Sekretaris Dewan',
      'Staf',
      'Fungsional Umum',
      'Analis Kebijakan',
      'Perencana',
      'Pranata Komputer',
      'Auditor',
      'Bendahara',
      'PPTK',
    ];

    foreach ($positions as $pos) {
      Position::updateOrCreate(['name' => $pos]);
    }
  }
}
