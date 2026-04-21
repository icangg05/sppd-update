<?php

namespace Database\Seeders;

use App\Enums\DepartmentType;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
  public function run(): void
  {
    // ── Sekretariat Daerah & Asisten ──
    // $sekda = Department::updateOrCreate(
    //   ['name' => 'Sekretariat Daerah'],
    //   ['code' => 'SETDA', 'type' => DepartmentType::SETDA, 'parent_id' => null]
    // );

    // $asistenPemerintahan = Department::updateOrCreate(
    //   ['name' => 'Asisten Pemerintahan dan Kesejahteraan Rakyat'],
    //   ['code' => 'ASIS-1', 'type' => DepartmentType::OPD, 'parent_id' => $sekda->id]
    // );

    // $asistenEkonomi = Department::updateOrCreate(
    //   ['name' => 'Asisten Perekonomian dan Pembangunan'],
    //   ['code' => 'ASIS-2', 'type' => DepartmentType::OPD, 'parent_id' => $sekda->id]
    // );

    // $asistenAdministrasi = Department::updateOrCreate(
    //   ['name' => 'Asisten Administrasi Umum'],
    //   ['code' => 'ASIS-3', 'type' => DepartmentType::OPD, 'parent_id' => $sekda->id]
    // );

    // ── Dinas-Dinas (OPD) ──
    $json = file_get_contents(storage_path('app/public/table_skpd.json'));
    $data = json_decode($json, true);

    // Pass 1: Insert all departments
    foreach ($data as $item) {
      $typeValue = $item['type'] ?? 'opd';
      $name = ucwords(strtolower($item['name']));

      Department::updateOrCreate(
        ['name' => $name],
        [
          'code'      => !empty($item['code']) ? $item['code'] : null,
          'type'      => DepartmentType::from($typeValue),
          'parent_id' => null
        ]
      );
    }

    // Pass 2: Assign parent_id
    // 1. Puskesmas -> Dinas Kesehatan
    $dinkes = Department::where('name', 'Dinas Kesehatan')->first();
    if ($dinkes) {
      Department::where('type', 'puskesmas')->update(['parent_id' => $dinkes->id]);
    }

    // 2. Bagian -> Sekretariat Daerah
    $setda = Department::where('name', 'Sekretariat Daerah')->first();
    if ($setda) {
      Department::where('name', 'like', 'Sekretariat Daerah Bagian%')->update(['parent_id' => $setda->id]);
    }

    // 3. Kelurahan -> Kecamatan (based on code prefix)
    $kecamatans = Department::where('type', 'kecamatan')->whereNotNull('code')->get();
    $kelurahans = Department::where('type', 'kelurahan')->whereNotNull('code')->get();

    foreach ($kecamatans as $kecamatan) {
      $prefix = implode('.', array_slice(explode('.', $kecamatan->code), 0, 3)); // e.g. 4.01.08
      foreach ($kelurahans as $kelurahan) {
        if (strpos($kelurahan->code, $prefix) === 0) {
          $kelurahan->update(['parent_id' => $kecamatan->id]);
        }
      }
    }
  }
}
