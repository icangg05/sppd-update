<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\Regency;
use Illuminate\Database\Seeder;

class ProvinceAndRegencySeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      'Aceh' => ['Kota Banda Aceh', 'Kota Sabang', 'Kota Langsa', 'Kota Lhokseumawe', 'Kab. Aceh Besar', 'Kab. Pidie'],
      'Sumatera Utara' => ['Kota Medan', 'Kota Binjai', 'Kota Pematang Siantar', 'Kota Tebing Tinggi', 'Kab. Deli Serdang', 'Kab. Langkat'],
      'Sumatera Barat' => ['Kota Padang', 'Kota Bukittinggi', 'Kota Payakumbuh', 'Kota Solok', 'Kab. Agam', 'Kab. Tanah Datar'],
      'Riau' => ['Kota Pekanbaru', 'Kota Dumai', 'Kab. Kampar', 'Kab. Bengkalis', 'Kab. Siak', 'Kab. Rokan Hilir'],
      'Jambi' => ['Kota Jambi', 'Kota Sungai Penuh', 'Kab. Muaro Jambi', 'Kab. Batanghari', 'Kab. Tanjung Jabung Barat'],
      'Sumatera Selatan' => ['Kota Palembang', 'Kota Prabumulih', 'Kota Pagar Alam', 'Kota Lubuk Linggau', 'Kab. OKU', 'Kab. OKI'],
      'Bengkulu' => ['Kota Bengkulu', 'Kab. Rejang Lebong', 'Kab. Bengkulu Utara', 'Kab. Seluma'],
      'Lampung' => ['Kota Bandar Lampung', 'Kota Metro', 'Kab. Lampung Selatan', 'Kab. Lampung Tengah', 'Kab. Lampung Utara'],
      'Kep. Bangka Belitung' => ['Kota Pangkal Pinang', 'Kab. Bangka', 'Kab. Belitung', 'Kab. Bangka Tengah'],
      'Kep. Riau' => ['Kota Batam', 'Kota Tanjung Pinang', 'Kab. Bintan', 'Kab. Karimun', 'Kab. Natuna'],
      'DKI Jakarta' => ['Kota Jakarta Pusat', 'Kota Jakarta Utara', 'Kota Jakarta Barat', 'Kota Jakarta Selatan', 'Kota Jakarta Timur', 'Kab. Kepulauan Seribu'],
      'Jawa Barat' => ['Kota Bandung', 'Kota Bogor', 'Kota Bekasi', 'Kota Depok', 'Kota Cimahi', 'Kota Cirebon', 'Kota Sukabumi', 'Kota Tasikmalaya', 'Kab. Bandung', 'Kab. Bogor', 'Kab. Garut', 'Kab. Cianjur'],
      'Jawa Tengah' => ['Kota Semarang', 'Kota Solo', 'Kota Magelang', 'Kota Salatiga', 'Kota Pekalongan', 'Kota Tegal', 'Kab. Semarang', 'Kab. Klaten', 'Kab. Boyolali', 'Kab. Banyumas'],
      'DI Yogyakarta' => ['Kota Yogyakarta', 'Kab. Sleman', 'Kab. Bantul', 'Kab. Kulon Progo', 'Kab. Gunung Kidul'],
      'Jawa Timur' => ['Kota Surabaya', 'Kota Malang', 'Kota Kediri', 'Kota Madiun', 'Kota Mojokerto', 'Kota Batu', 'Kab. Sidoarjo', 'Kab. Gresik', 'Kab. Jombang', 'Kab. Lamongan'],
      'Banten' => ['Kota Serang', 'Kota Tangerang', 'Kota Tangerang Selatan', 'Kota Cilegon', 'Kab. Tangerang', 'Kab. Serang', 'Kab. Pandeglang', 'Kab. Lebak'],
      'Bali' => ['Kota Denpasar', 'Kab. Badung', 'Kab. Gianyar', 'Kab. Tabanan', 'Kab. Buleleng', 'Kab. Karangasem'],
      'Nusa Tenggara Barat' => ['Kota Mataram', 'Kota Bima', 'Kab. Lombok Barat', 'Kab. Lombok Tengah', 'Kab. Lombok Timur', 'Kab. Sumbawa'],
      'Nusa Tenggara Timur' => ['Kota Kupang', 'Kab. Kupang', 'Kab. Ende', 'Kab. Flores Timur', 'Kab. Manggarai'],
      'Kalimantan Barat' => ['Kota Pontianak', 'Kota Singkawang', 'Kab. Kubu Raya', 'Kab. Sambas', 'Kab. Sanggau'],
      'Kalimantan Tengah' => ['Kota Palangka Raya', 'Kab. Kapuas', 'Kab. Kotawaringin Timur', 'Kab. Kotawaringin Barat'],
      'Kalimantan Selatan' => ['Kota Banjarmasin', 'Kota Banjarbaru', 'Kab. Banjar', 'Kab. Tanah Laut', 'Kab. Hulu Sungai Selatan'],
      'Kalimantan Timur' => ['Kota Samarinda', 'Kota Balikpapan', 'Kota Bontang', 'Kab. Kutai Kartanegara', 'Kab. Berau', 'Kab. Paser'],
      'Kalimantan Utara' => ['Kota Tarakan', 'Kab. Bulungan', 'Kab. Malinau', 'Kab. Nunukan', 'Kab. Tana Tidung'],
      'Sulawesi Utara' => ['Kota Manado', 'Kota Bitung', 'Kota Tomohon', 'Kota Kotamobagu', 'Kab. Minahasa'],
      'Sulawesi Tengah' => ['Kota Palu', 'Kab. Donggala', 'Kab. Parigi Moutong', 'Kab. Poso', 'Kab. Sigi'],
      'Sulawesi Selatan' => ['Kota Makassar', 'Kota Pare-Pare', 'Kota Palopo', 'Kab. Gowa', 'Kab. Maros', 'Kab. Bone', 'Kab. Bulukumba'],
      'Sulawesi Tenggara' => ['Kota Kendari', 'Kota Bau-Bau', 'Kab. Konawe', 'Kab. Muna', 'Kab. Kolaka'],
      'Gorontalo' => ['Kota Gorontalo', 'Kab. Gorontalo', 'Kab. Bone Bolango', 'Kab. Boalemo'],
      'Sulawesi Barat' => ['Kab. Majene', 'Kab. Polewali Mandar', 'Kab. Mamasa', 'Kab. Mamuju'],
      'Maluku' => ['Kota Ambon', 'Kota Tual', 'Kab. Maluku Tengah', 'Kab. Seram Bagian Barat'],
      'Maluku Utara' => ['Kota Ternate', 'Kota Tidore Kepulauan', 'Kab. Halmahera Utara', 'Kab. Halmahera Selatan'],
      'Papua' => ['Kota Jayapura', 'Kab. Jayapura', 'Kab. Merauke', 'Kab. Mimika'],
      'Papua Barat' => ['Kota Sorong', 'Kab. Sorong', 'Kab. Manokwari', 'Kab. Raja Ampat'],
      'Papua Selatan' => ['Kab. Merauke', 'Kab. Boven Digoel', 'Kab. Asmat', 'Kab. Mappi'],
      'Papua Tengah' => ['Kab. Nabire', 'Kab. Paniai', 'Kab. Mimika', 'Kab. Puncak Jaya'],
      'Papua Pegunungan' => ['Kab. Jayawijaya', 'Kab. Puncak', 'Kab. Yahukimo', 'Kab. Pegunungan Bintang'],
      'Papua Barat Daya' => ['Kota Sorong', 'Kab. Sorong Selatan', 'Kab. Maybrat', 'Kab. Tambrauw'],
    ];

    foreach ($data as $provName => $regencies) {
      $province = Province::updateOrCreate(['name' => $provName]);

      foreach ($regencies as $regName) {
        Regency::updateOrCreate(
          ['province_id' => $province->id, 'name' => $regName],
        );
      }
    }
  }
}
