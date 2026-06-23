<?php

namespace Database\Seeders;

use App\Enums\PositionScope;
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
    // Scope menentukan batas jumlah pemangku jabatan:
    //   SYSTEM     => hanya satu di seluruh sistem (mis. Walikota, Sekda)
    //   DEPARTMENT => hanya satu per OPD / unit kerja (mis. Kepala Dinas, Camat)
    //   NONE       => boleh banyak (mis. Staf, Fungsional Umum)
    $positions = [
      ['name' => 'Walikota', 'scope' => PositionScope::SYSTEM],
      ['name' => 'Wakil Walikota', 'scope' => PositionScope::SYSTEM],
      ['name' => 'Sekretaris Daerah', 'scope' => PositionScope::SYSTEM],
      ['name' => 'Asisten Pemerintahan dan Kesejahteraan Rakyat', 'scope' => PositionScope::SYSTEM],
      ['name' => 'Asisten Administrasi Umum', 'scope' => PositionScope::SYSTEM],
      ['name' => 'Asisten Perekonomian dan Pembangunan', 'scope' => PositionScope::SYSTEM],
      ['name' => 'Staf Ahli', 'scope' => PositionScope::NONE],
      ['name' => 'Kepala Dinas', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Sekretaris Dinas', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Kepala Bagian', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Kepala Bidang', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Kepala Seksi', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Kepala Sub Bagian', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Camat', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Sekretaris Camat', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Lurah', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Sekretaris Lurah', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Kepala Puskesmas', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Kepala Badan', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Inspektur', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Sekretaris Inspektur', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Sekretaris Dewan', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Staf', 'scope' => PositionScope::NONE],
      ['name' => 'Fungsional Umum', 'scope' => PositionScope::NONE],
      ['name' => 'Analis Kebijakan', 'scope' => PositionScope::NONE],
      ['name' => 'Pranata Komputer', 'scope' => PositionScope::NONE],
      ['name' => 'Auditor', 'scope' => PositionScope::NONE],
      ['name' => 'Bendahara Pengeluaran', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Bendahara Penerimaan', 'scope' => PositionScope::DEPARTMENT],
      ['name' => 'Bendahara Pembantu', 'scope' => PositionScope::NONE],
    ];

    foreach ($positions as $pos) {
      Position::updateOrCreate(
        ['name' => $pos['name']],
        ['uniqueness_scope' => $pos['scope']],
      );
    }
  }
}
