<?php

namespace App\Enums;

enum DprdJabatan: string
{
  // Urutan deklarasi = peringkat (0 tertinggi). Dikelompokkan per tingkat
  // jabatan: pimpinan DPRD, lalu Ketua/Wakil/Sekretaris/Anggota komisi.
  case KETUA   = 'Ketua DPRD';
  case WAKIL_1 = 'Wakil Ketua I';
  case WAKIL_2 = 'Wakil Ketua II';

  case KETUA_KOMISI_1 = 'Ketua Komisi I';
  case KETUA_KOMISI_2 = 'Ketua Komisi II';
  case KETUA_KOMISI_3 = 'Ketua Komisi III';

  case WAKIL_KOMISI_1 = 'Wakil Ketua Komisi I';
  case WAKIL_KOMISI_2 = 'Wakil Ketua Komisi II';
  case WAKIL_KOMISI_3 = 'Wakil Ketua Komisi III';

  case SEKRETARIS_KOMISI_1 = 'Sekretaris Komisi I';
  case SEKRETARIS_KOMISI_2 = 'Sekretaris Komisi II';
  case SEKRETARIS_KOMISI_3 = 'Sekretaris Komisi III';

  case KOMISI_1 = 'Anggota Komisi I';
  case KOMISI_2 = 'Anggota Komisi II';
  case KOMISI_3 = 'Anggota Komisi III';

  case ANGGOTA = 'Anggota';

  /**
   * Label jabatan. Bila $withRegion true, pimpinan DPRD (Ketua/Wakil Ketua)
   * diberi imbuhan wilayah dari config('sppd.dprd_region'), mis.
   * "Ketua DPRD Kota Kendari". Config kosong = tanpa imbuhan.
   */
  public function label(bool $withRegion = false): string
  {
    // Jabatan yang diberi imbuhan wilayah. Tambah/kurangi case di sini saja.
    $regional = [self::KETUA, self::WAKIL_1, self::WAKIL_2];

    if (! $withRegion || ! in_array($this, $regional, true) || ! ($region = config('sppd.dprd_region'))) {
      return $this->value;
    }

    // Bila nilai sudah memuat "DPRD" (mis. "Ketua DPRD") cukup tambah wilayah;
    // selainnya sisipkan "DPRD" dulu ("Wakil Ketua I" → "... DPRD Kota Kendari").
    return str_contains($this->value, 'DPRD')
      ? "{$this->value} {$region}"
      : "{$this->value} DPRD {$region}";
  }

  /**
   * Peringkat jabatan (0 = tertinggi). Diturunkan dari urutan deklarasi case,
   * yang memang disusun dari Ketua DPRD ke Anggota.
   */
  public function level(): int
  {
    return array_search($this, self::cases(), true);
  }
}
