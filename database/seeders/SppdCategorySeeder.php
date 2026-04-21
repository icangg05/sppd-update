<?php

namespace Database\Seeders;

use App\Models\SppdCategory;
use Illuminate\Database\Seeder;

class SppdCategorySeeder extends Seeder
{
  public function run(): void
  {
    $categories = [
      ['name' => 'Konsultasi', 'description' => 'Perjalanan dinas untuk konsultasi ke instansi terkait'],
      ['name' => 'Koordinasi', 'description' => 'Perjalanan dinas untuk koordinasi antar instansi'],
      ['name' => 'Sosialisasi', 'description' => 'Perjalanan dinas untuk sosialisasi program/kebijakan'],
      ['name' => 'Monitoring & Evaluasi', 'description' => 'Perjalanan dinas untuk monitoring dan evaluasi program'],
      ['name' => 'Bimbingan Teknis', 'description' => 'Perjalanan dinas untuk bimbingan teknis/pelatihan'],
      ['name' => 'Rapat/Pertemuan', 'description' => 'Perjalanan dinas untuk menghadiri rapat atau pertemuan'],
      ['name' => 'Studi Banding', 'description' => 'Perjalanan dinas untuk studi banding ke daerah lain'],
      ['name' => 'Pengawasan', 'description' => 'Perjalanan dinas untuk pengawasan dan pemeriksaan'],
      ['name' => 'Pelantikan/Sertijab', 'description' => 'Perjalanan dinas untuk pelantikan atau serah terima jabatan'],
      ['name' => 'Lainnya', 'description' => 'Perjalanan dinas dengan kategori lainnya'],
    ];

    foreach ($categories as $category) {
      SppdCategory::updateOrCreate(
        ['name' => $category['name']],
        $category
      );
    }
  }
}
