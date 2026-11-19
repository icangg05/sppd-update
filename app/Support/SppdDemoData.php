<?php

namespace App\Support;

use App\Enums\SppdDomain;
use App\Models\Budget;
use App\Models\Country;
use App\Models\ForeignCity;
use App\Models\Province;
use App\Models\Regency;
use App\Models\SppdCategory;
use App\Models\SppdFollower;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Data uji realistis untuk formulir Detail SPPD (tombol "Isi Data Uji"), hanya untuk
 * APP_ENV local/development. Teks, tujuan, angkutan, dan lama perjalanan mengikuti domain;
 * tujuan selalu diambil dari data master (provinsi/kota/negara) dan tanggal dipilih yang
 * tidak bentrok, sehingga formulir yang terisi langsung lolos validasi.
 */
class SppdDemoData
{
  /** Tempat tujuan dalam daerah (Kota Kendari). */
  private const DALAM_DAERAH_PLACES = [
    'Kantor Camat Poasia',
    'Kantor Camat Mandonga',
    'Kantor Camat Kambu',
    'Kantor Camat Abeli',
    'Kantor Camat Puuwatu',
    'Kantor Lurah Anduonohu, Kecamatan Poasia',
    'Kantor Lurah Kadia, Kecamatan Kadia',
    'Puskesmas Lepo-Lepo, Kecamatan Baruga',
    'Kantor DPRD Kota Kendari',
  ];

  /** Kabupaten/kota kepulauan di Sulawesi Tenggara: dicapai lewat laut (Wakatobi lewat udara). */
  private const ISLAND_REGENCIES = ['Muna', 'Buton', 'Baubau', 'Wakatobi', 'Konawe Kepulauan'];

  /** Tujuan luar provinsi: [provinsi, kabupaten/kota, nama instansi untuk perihal, alamat]. */
  private const LDLP_PLACES = [
    ['DKI Jakarta', 'Kota Jakarta Pusat', 'Kementerian Dalam Negeri RI', 'Kementerian Dalam Negeri RI, Jl. Medan Merdeka Utara No. 7'],
    ['DKI Jakarta', 'Kota Jakarta Pusat', 'Kementerian Komunikasi dan Digital RI', 'Kementerian Komunikasi dan Digital RI, Jl. Medan Merdeka Barat No. 9'],
    ['DKI Jakarta', 'Kota Jakarta Selatan', 'Kementerian Kesehatan RI', 'Kementerian Kesehatan RI, Jl. H.R. Rasuna Said Blok X-5 Kav. 4-9'],
    ['DKI Jakarta', 'Kota Jakarta Selatan', 'Kementerian PAN-RB', 'Kementerian PAN-RB, Jl. Jend. Sudirman Kav. 69'],
    ['Sulawesi Selatan', 'Kota Makassar', 'Pemerintah Provinsi Sulawesi Selatan', 'Kantor Gubernur Sulawesi Selatan, Jl. Urip Sumoharjo No. 269'],
    ['Jawa Barat', 'Kota Bandung', 'Pemerintah Provinsi Jawa Barat', 'Gedung Sate, Jl. Diponegoro No. 22'],
    ['Jawa Timur', 'Kota Surabaya', 'Pemerintah Provinsi Jawa Timur', 'Kantor Gubernur Jawa Timur, Jl. Pahlawan No. 110'],
    ['Bali', 'Kota Denpasar', 'Pemerintah Provinsi Bali', 'Kantor Gubernur Bali, Jl. Basuki Rahmat, Niti Mandala Renon'],
    ['DI Yogyakarta', 'Kota Yogyakarta', 'Pemerintah Daerah DI Yogyakarta', 'Kompleks Kepatihan Pemda DIY, Jl. Malioboro'],
    ['Sulawesi Utara', 'Kota Manado', 'Pemerintah Provinsi Sulawesi Utara', 'Kantor Gubernur Sulawesi Utara, Jl. 17 Agustus'],
  ];

  /** Tujuan luar negeri: [negara, kota, instansi & alamat]. */
  private const LUAR_NEGERI_PLACES = [
    ['Malaysia', 'Kuala Lumpur', 'Kedutaan Besar Republik Indonesia, Jalan Tun Razak No. 233'],
    ['Singapura', 'Singapore', 'Kedutaan Besar Republik Indonesia, 7 Chatsworth Road'],
    ['Jepang', 'Tokyo', 'Kedutaan Besar Republik Indonesia, 5-2-9 Higashi Gotanda, Shinagawa-ku'],
    ['Korea Selatan', 'Seoul', 'Kedutaan Besar Republik Indonesia, 380 Yeouidaebang-ro, Yeongdeungpo-gu'],
    ['Thailand', 'Bangkok', 'Kedutaan Besar Republik Indonesia, 600-602 Petchburi Road, Ratchathewi'],
    ['Australia', 'Canberra', 'Kedutaan Besar Republik Indonesia, 8 Darwin Avenue, Yarralumla'],
    ['Tiongkok', 'Beijing', 'Kedutaan Besar Republik Indonesia, Dongzhimenwai Dajie No. 4, Chaoyang'],
  ];

  public function __construct(
    private User $pelaksana,
    private SppdDomain $domain,
  ) {}

  /** Tombol & aksi isi data uji hanya ada di APP_ENV local/development. */
  public static function enabled(): bool
  {
    return app()->environment('local', 'development');
  }

  /**
   * Nilai seluruh field formulir, dengan kunci = nama property SppdCreateDetails.
   *
   * @param  Collection<int, Budget>  $budgets  DPA yang boleh dipilih pelaksana
   * @param  Collection<int, User>  $followerCandidates  calon pengikut (tanpa pelaksana)
   * @param  bool  $checkClash  false untuk SPPD Inspektorat (bentrok tanggal tidak dicek)
   * @param  int|null  $exceptId  SPPD yang sedang diperbaiki, tidak dihitung bentrok
   */
  public function make(Collection $budgets, Collection $followerCandidates, bool $checkClash, ?int $exceptId = null): array
  {
    [$destinations, $tujuan, $transport] = $this->destinations();
    $story = $this->narrative($tujuan);
    [$start, $end] = $this->travelPeriod($this->durationDays(), $checkClash, $exceptId);

    // Pengikut 1–2 orang yang bebas pada tanggal perjalanan.
    $busy = $checkClash ? SppdRequest::clashingForUsers($followerCandidates, $start, $end, $exceptId) : [];
    $followers = $followerCandidates
      ->reject(fn (User $u) => isset($busy[$u->id]))
      ->shuffle()
      ->take(random_int(1, 2))
      ->pluck('id')
      ->all();

    // DPA yang masih bersisa lebih dulu; bila semua habis, tetap pilih salah satu.
    $budget = $budgets->filter(fn (Budget $b) => $b->balance > 0)->whenEmpty(fn () => $budgets)->shuffle()->first();
    $category = SppdCategory::where('name', $story['category'])->first() ?? SppdCategory::inRandomOrder()->first();

    $data = [
      'purpose' => $story['purpose'],
      'problem' => $story['problem'],
      'facts' => $story['facts'],
      'analysis' => $story['analysis'],
      'category_id' => $category?->id,
      'budget_id' => $budget?->id,
      'start_date' => $start->format('Y-m-d'),
      'end_date' => $end->format('Y-m-d'),
      'transport_type' => $transport[0],
      'transport_choice' => $transport[1],
      'transport_name' => $transport[1],
      'departure_place' => $this->departurePlace(),
      'urgency' => random_int(1, 4) === 1 ? 'Segera' : 'Biasa',
      'spt_date' => now()->format('Y-m-d'),
      'sppd_date' => now()->format('Y-m-d'),
      'destinations' => $destinations,
      'followers' => $followers,
      'searchFollower' => '',
      'travel_position' => '',
      'follower_positions' => [],
    ];

    $department = $this->pelaksana->department;
    if ($department?->usesManualNumbering()) {
      $data['document_number'] = '090 / ' . random_int(100, 999) . ' / ST / INSP./ ' . now()->year;
    }

    // Inspektorat dalam daerah: susunan tim (pelaksana Ketua Tim, pengikut Anggota).
    if ($department?->usesManualNumbering() && $this->domain->usesTravelPositions()) {
      $data['travel_position'] = 'Ketua Tim';
      $data['follower_positions'] = array_fill_keys($followers, 'Anggota');
    }

    return $data;
  }

  /** Lama perjalanan (hari) yang lazim untuk domain ini. */
  private function durationDays(): int
  {
    return match ($this->domain) {
      SppdDomain::DALAM_DAERAH => random_int(1, 3),
      SppdDomain::LDDP => random_int(2, 4),
      SppdDomain::LDLP => random_int(3, 5),
      SppdDomain::LUAR_NEGERI => random_int(5, 7),
    };
  }

  /**
   * Berangkat 3–14 hari lagi, digeser melewati SPPD lain pelaksana bila bentrok. Dalam daerah
   * tidak berangkat/kembali pada hari yang tidak dihitung (Minggu, juga Sabtu bila instansi
   * pelaksana tidak menghitung Sabtu).
   *
   * @return array{0: Carbon, 1: Carbon}
   */
  private function travelPeriod(int $days, bool $checkClash, ?int $exceptId): array
  {
    $start = now()->startOfDay()->addDays(random_int(3, 14));
    $countsSaturday = $this->pelaksana->department?->countsSaturdayOnTrips() ?? true;

    for ($attempt = 0; $attempt < 60; $attempt++) {
      while ($this->domain->isOffDay($start, $countsSaturday)) {
        $start->addDay();
      }

      $end = $start->copy();
      while ($this->domain->travelDays($start, $end, $countsSaturday) < $days || $this->domain->isOffDay($end, $countsSaturday)) {
        $end->addDay();
      }

      $clash = $checkClash
        ? SppdRequest::clashingForUsers(collect([$this->pelaksana]), $start, $end, $exceptId)[$this->pelaksana->id] ?? null
        : null;
      if (! $clash) {
        return [$start, $end];
      }

      $start = $clash->end_date->copy()->startOfDay()->addDay();
    }

    return [$start, $end]; // Jadwal sangat padat: biarkan validasi formulir yang menolak.
  }

  /**
   * Baris tujuan (bentuk $destinations di SppdCreateDetails), nama singkat tujuan untuk
   * teks perihal, dan angkutan [jenis, kendaraan].
   *
   * @return array{0: list<array>, 1: string, 2: array{0: string, 1: string}}
   */
  private function destinations(): array
  {
    return match ($this->domain) {
      SppdDomain::DALAM_DAERAH => $this->dalamDaerah(),
      SppdDomain::LDDP => $this->lddp(),
      SppdDomain::LDLP => $this->ldlp(),
      SppdDomain::LUAR_NEGERI => $this->luarNegeri(),
    };
  }

  private function dalamDaerah(): array
  {
    $place = Arr::random(self::DALAM_DAERAH_PLACES);

    return [[$this->row(['address_only' => $place])], $place, ['Darat', 'Mobil']];
  }

  /** 1–2 kabupaten/kota di provinsi induk selain tempat kedudukan. */
  private function lddp(): array
  {
    $province = Province::where('name', config('sppd.default_province_name'))->first();
    $regencies = Regency::where('province_id', $province?->id)
      ->where('name', 'NOT LIKE', '%' . config('sppd.home_base_regency_keyword') . '%')
      ->inRandomOrder()
      ->limit(random_int(1, 2))
      ->get();

    $rows = $regencies->map(function (Regency $regency) use ($province) {
      [$jenis, $nama] = $this->splitRegencyName($regency->name);
      $address = Arr::random($jenis === 'Kota'
        ? ["Kantor Wali Kota {$nama}", "Sekretariat Daerah Kota {$nama}", "Kantor Bappeda Kota {$nama}"]
        : ["Kantor Bupati {$nama}", "Sekretariat Daerah Kabupaten {$nama}", "Kantor Bappeda Kabupaten {$nama}"]);

      return $this->row(['province_id' => $province->id, 'regency_id' => $regency->id, 'address' => $address]);
    })->all();

    $names = $regencies->map(fn (Regency $r) => $this->splitRegencyName($r->name)[1]);
    $first = $regencies->first();
    $tujuan = $first ? 'Pemerintah ' . implode(' ', $this->splitRegencyName($first->name)) : 'kabupaten/kota tujuan';

    // Wakatobi lewat udara, daerah kepulauan lain lewat laut, sisanya darat.
    $isIsland = fn (string $name) => collect(self::ISLAND_REGENCIES)->contains(fn ($island) => str_contains($name, $island));
    $transport = match (true) {
      $names->contains(fn ($n) => str_contains($n, 'Wakatobi')) => ['Udara', 'Pesawat'],
      $names->contains($isIsland) => ['Laut', Arr::random(['Kapal', 'Speedboat'])],
      default => ['Darat', 'Mobil'],
    };

    return [$rows ?: [$this->row()], $tujuan, $transport];
  }

  private function ldlp(): array
  {
    [$provinceName, $regencyName, $tujuan, $address] = Arr::random(self::LDLP_PLACES);
    $province = Province::where('name', $provinceName)->first();
    $regency = $province ? Regency::where('province_id', $province->id)->where('name', $regencyName)->first() : null;

    // Data master berbeda (mis. database uji): ambil kabupaten/kota mana pun di luar provinsi induk.
    if (! $regency) {
      $regency = Regency::whereHas('province', fn ($q) => $q->where('name', '!=', config('sppd.default_province_name')))
        ->inRandomOrder()->first();
      $province = $regency?->province;
      $tujuan = 'Pemerintah ' . ($regency?->name ?? 'Daerah Tujuan');
      $address = 'Kantor ' . $tujuan;
    }

    $row = $this->row(['province_id' => $province?->id ?? '', 'regency_id' => $regency?->id ?? '', 'address' => $address]);

    return [[$row], $tujuan, ['Udara', 'Pesawat']];
  }

  private function luarNegeri(): array
  {
    [$countryName, $cityName, $address] = Arr::random(self::LUAR_NEGERI_PLACES);
    $city = ForeignCity::with('country')
      ->where('name', $cityName)
      ->whereHas('country', fn ($q) => $q->where('name', $countryName))
      ->first();

    // Data master berbeda: negara & kota mana pun yang terdaftar.
    if (! $city) {
      $city = ForeignCity::with('country')->inRandomOrder()->first();
      $address = 'Kedutaan Besar Republik Indonesia di ' . ($city?->name ?? 'kota tujuan');
    }

    $country = $city?->country?->name ?? Country::inRandomOrder()->value('name') ?? '';
    $row = $this->row([
      'country' => $country,
      'city' => $city?->name ?? 'Kota Tujuan',
      // Nilai pilihan dropdown kota = nama kota dari data master.
      'city_choice' => $city?->name ?? '__other__',
      'address' => $address,
    ]);

    return [[$row], trim(($city?->name ?? '') . ', ' . $country, ', '), ['Udara', 'Pesawat']];
  }

  /** Baris tujuan lengkap dengan kunci yang dipakai formulir. */
  private function row(array $values = []): array
  {
    return array_merge([
      'province_id' => '',
      'regency_id' => '',
      'country' => '',
      'city' => '',
      'city_choice' => '',
      'address' => '',
      'address_only' => '',
    ], $values);
  }

  /** "Kab. Konawe Selatan" → ['Kabupaten', 'Konawe Selatan']; "Kota Baubau" → ['Kota', 'Baubau']. */
  private function splitRegencyName(string $name): array
  {
    if (preg_match('/^(Kab\.|Kabupaten)\s+(.+)$/i', $name, $m)) {
      return ['Kabupaten', $m[2]];
    }
    if (preg_match('/^Kota\s+(.+)$/i', $name, $m)) {
      return ['Kota', $m[1]];
    }

    return ['Kabupaten', $name];
  }

  private function departurePlace(): string
  {
    if ($this->domain !== SppdDomain::DALAM_DAERAH) {
      return 'Kendari';
    }

    $name = $this->pelaksana->department?->name ?? 'Sekretariat Daerah';

    return 'Kantor ' . $name . (str_contains($name, 'Kendari') ? '' : ' Kota Kendari');
  }

  /**
   * Perihal, persoalan, fakta, dan analisis bergaya telaah staf, sesuai domain. Poin ditulis
   * satu per baris dengan awalan "-" (format yang dikenali dokumen).
   *
   * @return array{category: string, purpose: string, problem: string, facts: string, analysis: string}
   */
  private function narrative(string $tujuan): array
  {
    $isInspektorat = (bool) $this->pelaksana->department?->usesManualNumbering();

    $templates = match ($this->domain) {
      SppdDomain::DALAM_DAERAH => [
        [
          'category' => 'Monitoring & Evaluasi',
          'purpose' => 'Melaksanakan monitoring dan evaluasi pelaksanaan program kerja {opd} Tahun Anggaran {tahun} di {tujuan}',
          'problem' => "- Realisasi kegiatan triwulan III belum mencapai target yang ditetapkan\n- Format laporan capaian dari unit pelaksana belum seragam",
          'facts' => "- Rencana Kerja {opd} Tahun {tahun}\n- Laporan realisasi kegiatan triwulan III Tahun {tahun}",
          'analysis' => "- Peninjauan langsung diperlukan untuk memverifikasi data capaian di lapangan\n- Hasil monitoring menjadi bahan rekomendasi perbaikan sebelum akhir tahun anggaran",
        ],
        [
          'category' => 'Sosialisasi',
          'purpose' => 'Melaksanakan sosialisasi standar pelayanan publik kepada aparatur di {tujuan}',
          'problem' => "- Pemahaman aparatur terhadap standar pelayanan publik masih beragam\n- Masih terdapat keluhan masyarakat terkait waktu penyelesaian layanan",
          'facts' => "- Undang-Undang Nomor 25 Tahun 2009 tentang Pelayanan Publik\n- Hasil survei kepuasan masyarakat semester I Tahun {tahun}",
          'analysis' => 'Sosialisasi tatap muka paling efektif agar aparatur dapat langsung berdiskusi dan menyamakan pemahaman tentang standar pelayanan.',
        ],
        [
          'category' => 'Koordinasi',
          'purpose' => 'Melaksanakan koordinasi verifikasi usulan kegiatan prioritas Tahun {tahun_depan} di {tujuan}',
          'problem' => "- Data usulan kegiatan prioritas belum terverifikasi\n- Terdapat perbedaan antara usulan musrenbang dan kondisi di lapangan",
          'facts' => "- Surat Sekretaris Daerah Kota Kendari Nomor 050/{nomor}/{tahun} tentang verifikasi usulan kegiatan\n- Berita acara Musrenbang Kecamatan Tahun {tahun}",
          'analysis' => 'Verifikasi bersama perangkat kecamatan diperlukan agar usulan yang masuk ke rancangan anggaran sesuai dengan kebutuhan nyata masyarakat.',
        ],
        ...($isInspektorat ? [[
          'category' => 'Pengawasan',
          'purpose' => 'Melaksanakan pemeriksaan reguler atas pengelolaan keuangan dan aset Tahun Anggaran {tahun} pada {tujuan}',
          'problem' => "- Terdapat temuan berulang terkait penatausahaan aset pada pemeriksaan tahun sebelumnya\n- Tindak lanjut rekomendasi hasil pemeriksaan belum tuntas",
          'facts' => "- Program Kerja Pengawasan Tahunan (PKPT) Inspektorat Tahun {tahun}\n- Laporan Hasil Pemeriksaan Tahun {tahun_lalu}",
          'analysis' => 'Pemeriksaan langsung diperlukan untuk memastikan tindak lanjut rekomendasi dan mencegah temuan berulang pada pemeriksaan eksternal.',
        ]] : []),
      ],
      SppdDomain::LDDP => [
        [
          'category' => 'Koordinasi',
          'purpose' => 'Melaksanakan koordinasi program kerja sama antardaerah dengan {tujuan}',
          'problem' => "- Belum ada kesepakatan teknis pelaksanaan kerja sama antardaerah\n- Pembagian peran masing-masing perangkat daerah belum jelas",
          'facts' => "- Nota Kesepahaman antara Pemerintah Kota Kendari dan {tujuan}\n- Surat undangan rapat koordinasi Nomor 005/{nomor}/{tahun}",
          'analysis' => 'Koordinasi langsung diperlukan agar perjanjian kerja sama dapat ditandatangani pada triwulan berjalan.',
        ],
        [
          'category' => 'Studi Banding',
          'purpose' => 'Melaksanakan studi tiru penerapan layanan berbasis elektronik pada {tujuan}',
          'problem' => "- Sebagian layanan administrasi pada {opd} masih dilakukan secara manual\n- Waktu penyelesaian layanan belum sesuai standar",
          'facts' => "- {tujuan} meraih predikat baik pada evaluasi SPBE Tahun {tahun_lalu}\n- Rencana penerapan layanan elektronik pada {opd} Tahun {tahun_depan}",
          'analysis' => 'Pengalaman daerah yang telah berhasil dapat diadopsi agar penerapan layanan elektronik di Kota Kendari lebih cepat dan tepat sasaran.',
        ],
        [
          'category' => 'Rapat/Pertemuan',
          'purpose' => 'Menghadiri Rapat Koordinasi Regional se-Sulawesi Tenggara yang diselenggarakan oleh {tujuan}',
          'problem' => "- Program prioritas kabupaten/kota se-Sulawesi Tenggara perlu diselaraskan\n- Data pendukung dari Kota Kendari wajib dipaparkan dalam rapat",
          'facts' => "- Surat undangan Nomor 005/{nomor}/{tahun} perihal Rapat Koordinasi Regional\n- Agenda rapat memuat pemaparan capaian tiap kabupaten/kota",
          'analysis' => 'Kehadiran langsung diperlukan agar kepentingan Kota Kendari terakomodasi dalam kesepakatan regional.',
        ],
      ],
      SppdDomain::LDLP => [
        [
          'category' => 'Konsultasi',
          'purpose' => 'Melaksanakan konsultasi penyusunan dokumen perencanaan dan penganggaran Tahun {tahun_depan} pada {tujuan}',
          'problem' => "- Terdapat perubahan regulasi terkait nomenklatur program dan kegiatan\n- Penyesuaian dokumen perencanaan memerlukan arahan teknis dari kementerian",
          'facts' => "- Peraturan Menteri Dalam Negeri tentang Pedoman Penyusunan APBD Tahun {tahun_depan}\n- Jadwal penetapan APBD Kota Kendari Tahun {tahun_depan}",
          'analysis' => 'Konsultasi langsung diperlukan agar dokumen perencanaan sesuai regulasi terbaru sebelum batas waktu penetapan.',
        ],
        [
          'category' => 'Bimbingan Teknis',
          'purpose' => 'Mengikuti Bimbingan Teknis Peningkatan Kapasitas Aparatur yang diselenggarakan oleh {tujuan}',
          'problem' => "- Kompetensi aparatur pengelola kegiatan pada {opd} perlu ditingkatkan\n- Terdapat aplikasi dan regulasi baru yang wajib diterapkan tahun ini",
          'facts' => "- Surat undangan bimbingan teknis Nomor 800/{nomor}/{tahun}\n- Rencana pengembangan kompetensi ASN {opd} Tahun {tahun}",
          'analysis' => 'Bimbingan teknis langsung dari instansi pembina memastikan aparatur memahami ketentuan baru dan dapat segera menerapkannya.',
        ],
        [
          'category' => 'Koordinasi',
          'purpose' => 'Melaksanakan koordinasi dan sinkronisasi program prioritas nasional dengan {tujuan}',
          'problem' => "- Program prioritas daerah belum sepenuhnya selaras dengan program prioritas nasional\n- Peluang dukungan pendanaan pusat belum dimanfaatkan optimal",
          'facts' => "- Rencana Kerja Pemerintah Tahun {tahun_depan}\n- Surat Wali Kota Kendari Nomor 050/{nomor}/{tahun} perihal usulan dukungan program",
          'analysis' => 'Koordinasi tatap muka mempercepat penyelarasan program sekaligus membuka peluang dukungan pendanaan dari pemerintah pusat.',
        ],
      ],
      SppdDomain::LUAR_NEGERI => [
        [
          'category' => 'Studi Banding',
          'purpose' => 'Melaksanakan studi banding pengelolaan kota cerdas (smart city) di {tujuan}',
          'problem' => "- Integrasi data layanan publik Kota Kendari belum optimal\n- Pemanfaatan teknologi dalam pengambilan kebijakan masih terbatas",
          'facts' => "- Masterplan Smart City Kota Kendari Tahun {tahun_lalu}–{tahun_depan}\n- Surat persetujuan perjalanan dinas luar negeri dari Kementerian Dalam Negeri",
          'analysis' => 'Pembelajaran langsung dari kota yang telah maju dapat mempercepat penerapan kota cerdas di Kota Kendari secara terukur.',
        ],
        [
          'category' => 'Rapat/Pertemuan',
          'purpose' => 'Menghadiri forum kerja sama internasional pemerintah daerah di {tujuan}',
          'problem' => "- Jejaring kerja sama luar negeri Pemerintah Kota Kendari masih terbatas\n- Promosi potensi investasi daerah belum menjangkau mitra internasional",
          'facts' => "- Undangan resmi penyelenggara forum melalui Perwakilan RI\n- Surat persetujuan perjalanan dinas luar negeri dari Kementerian Dalam Negeri",
          'analysis' => 'Kehadiran dalam forum membuka peluang kerja sama dan promosi potensi daerah secara langsung kepada mitra internasional.',
        ],
      ],
    };

    $replace = [
      '{tujuan}' => $tujuan,
      '{opd}' => $this->pelaksana->department?->name ?? 'Pemerintah Kota Kendari',
      '{tahun}' => (string) now()->year,
      '{tahun_depan}' => (string) (now()->year + 1),
      '{tahun_lalu}' => (string) (now()->year - 1),
      '{nomor}' => (string) random_int(100, 999),
    ];

    return array_map(fn (string $text) => strtr($text, $replace), Arr::random($templates));
  }
}
