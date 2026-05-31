<?php

namespace Database\Seeders;

use App\Enums\DepartmentType;
use App\Helpers\SmartTitle;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
  public function run(): void
  {
    // ── Dinas-Dinas (OPD) ──
    $json = file_get_contents(storage_path('app/public/json/table_skpd.json'));
    $data = json_decode($json, true);

    // Pass 1: Insert all departments
    foreach ($data as $item) {
      $typeValue = $item['type'] ?? 'opd';
      $name = SmartTitle::convert($item['name']);

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


    // ── Sekretariat Daerah => Asisten, Bagian & Sub Bagian ──
    $strukturOrganisasi = [
      [
        'asisten' => 'Asisten Administrasi Pemerintahan dan Kesra',
        'bagian' => [
          [
            'nama' => 'Bagian Adm. Pemerintahan dan Otonomi Daerah',
            'sub_bagian' => [
              'Sub Bagian Pemerintahan Umum',
              'Sub Bagian Bina Kecamatan dan Kelurahan',
              'Sub Bagian Tata Pemerintahan dan Otonomi Daerah'
            ]
          ],
          [
            'nama' => 'Bagian Hukum dan Hak Asasi Manusia',
            'sub_bagian' => [
              'Sub Bagian Produk Hukum Daerah',
              'Sub Bagian Bantuan Hukum',
              'Sub Bagian Dokumentasi'
            ]
          ],
          [
            'nama' => 'Bagian Adm. Pemberdayaan Masyarakat',
            'sub_bagian' => [
              'Sub Bagian Kelembagaan Masyarakat',
              'Sub Bagian Pengembangan Teknologi Tepat',
              'Sub Bagian Sosial Budaya'
            ]
          ],
          [
            'nama' => 'Bagian Adm. Kesejahteraan Rakyat',
            'sub_bagian' => [
              'Sub Bagian Sosial dan Kesejahteraan Rakyat',
              'Sub Bagian Mental Spiritual',
              'Sub Bagian Pembinaan dan Penyuluhan'
            ]
          ],
          [
            'nama' => 'Bagian Kerjasama',
            'sub_bagian' => [
              'Sub Bagian Kerjasama Dalam dan Luar Negeri',
              'Sub Bagian Kerjasama Daerah',
              'Sub Bagian Hubungan Antar Lembaga Pemerintahan'
            ]
          ]
        ]
      ],
      [
        'asisten' => 'Asisten Administrasi Perekonomian dan Pembangunan',
        'bagian' => [
          [
            'nama' => 'Bagian Perekonomian',
            'sub_bagian' => [
              'Sub Bagian Bina Produksi',
              'Sub Bagian Pembinaan BUMD',
              'Sub Bagian Perencanaan dan Pengawasan Ekonomi Mikro'
            ]
          ],
          [
            'nama' => 'Bagian Adm. Pembangunan',
            'sub_bagian' => [
              'Sub Bagian Penyusunan Program dan Standarisasi',
              'Sub Bagian Pengendalian Pembangunan dan Pengadaan',
              'Sub Bagian Evaluasi dan Pelaporan'
            ]
          ],
          [
            'nama' => 'Bagian Adm. Sumber Daya Alam',
            'sub_bagian' => [
              'Sub Bagian Lingkungan Hidup dan Sumber Daya Mineral',
              'Sub Bagian Energi',
              'Sub Bagian Tata Kelola Sumber Daya Mineral'
            ]
          ],
          [
            'nama' => 'Bagian Pengadaan Barang dan Jasa',
            'sub_bagian' => [
              'Sub Bagian Pengelolaan Layanan Pengadaan Secara Elektronik',
              'Sub Bagian Pengelolaan Pengadaan Barang dan Jasa',
              'Sub Bagian Pembinaan dan Advokasi Pengadaan Barang'
            ]
          ]
        ]
      ],
      [
        'asisten' => 'Asisten Administrasi Umum',
        'bagian' => [
          [
            'nama' => 'Bagian Organisasi dan Pemberdayaan Aparatur',
            'sub_bagian' => [
              'Sub Bagian Kelembagaan Analisa Jabatan',
              'Sub Bagian Tatalaksana dan Reformasi Birokrasi',
              'Sub Bagian Pemberdayaan Aparatur'
            ]
          ],
          [
            'nama' => 'Bagian Adm. Humas dan Protokol',
            'sub_bagian' => [
              'Sub Bagian Humas',
              'Sub Bagian Perjalanan Dinas dan Protokol',
              'Sub Bagian e-Data dan Informasi'
            ]
          ],
          [
            'nama' => 'Bagian Adm. Umum dan Perlengkapan',
            'sub_bagian' => [
              'Sub Bagian Tata Usaha Pimpinan dan Staf Ahli',
              'Sub Bagian Rumah Tangga'
            ]
          ],
          [
            'nama' => 'Bagian Perencanaan dan Keuangan Setda',
            'sub_bagian' => [
              'Sub Bagian Perencanaan',
              'Sub Bagian Keuangan',
              'Sub Bagian Pelaporan',
              'Sub Bagian Pengelola Aset'
            ]
          ]
        ]
      ]
    ];

    // Proses Looping untuk Seeding ke Database secara Hierarkis
    foreach ($strukturOrganisasi as $dataAsisten) {
      // 1. Insert/Update Asisten
      $asisten = Department::updateOrCreate(
        ['name' => $dataAsisten['asisten']],
        ['code' => '', 'type' => DepartmentType::ASISTEN, 'parent_id' => 3]
      );

      foreach ($dataAsisten['bagian'] as $dataBagian) {
        // 2. Insert/Update Bagian (menggunakan id asisten sebagai parent_id)
        $bagian = Department::updateOrCreate(
          ['name' => $dataBagian['nama']],
          ['code' => '', 'type' => DepartmentType::BAGIAN, 'parent_id' => $asisten->id]
        );

        foreach ($dataBagian['sub_bagian'] as $namaSubBagian) {
          // 3. Insert/Update Sub Bagian (menggunakan id bagian sebagai parent_id)
          Department::updateOrCreate(
            ['name' => $namaSubBagian],
            ['code' => '', 'type' => DepartmentType::SUBBAGIAN, 'parent_id' => $bagian->id]
          );
        }
      }
    }
  }
}
