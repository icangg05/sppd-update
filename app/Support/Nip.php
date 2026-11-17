<?php

namespace App\Support;

/**
 * Membaca senioritas dari NIP 18 digit, sebagai pembeda urutan seperti Daftar Urut Kepangkatan:
 * masa kerja lebih lama (TMT lebih awal) di atas, lalu usia lebih tua di atas.
 *
 * - PNS: tanggal lahir (8 digit) + TMT CPNS tahun-bulan (6) + jenis kelamin (1) + nomor urut (3).
 * - PPPK: tanggal lahir (8) + tahun pengangkatan (4) + kode "21" + jenis kelamin (1) + nomor urut (3).
 *   Bulannya tidak ada, jadi dianggap akhir tahun (di bawah PNS yang diangkat pada tahun yang sama).
 *
 * Bagian yang rusak (mis. tanggal "00", bulan 13) dianggap tidak diketahui.
 */
class Nip
{
  /**
   * Kunci urut senioritas: makin kecil makin senior. Bagian yang tidak diketahui ditaruh di bawah
   * yang diketahui, jadi pegawai tanpa NIP sah berada paling bawah di antara yang setara.
   *
   * @return array{0: int, 1: string, 2: int, 3: string} [tmt tak diketahui?, TMT YYYYMM, lahir tak diketahui?, lahir YYYYMMDD]
   */
  public static function seniorityKey(?string $nip): array
  {
    $tmt = self::tmt($nip);
    $birth = self::birthDate($nip);

    return [$tmt === null ? 1 : 0, $tmt ?? '', $birth === null ? 1 : 0, $birth ?? ''];
  }

  /** TMT pengangkatan "YYYYMM" (PPPK: "YYYY13" karena bulannya tidak ada), atau null. */
  public static function tmt(?string $nip): ?string
  {
    $digits = self::digits($nip);
    if ($digits === null) {
      return null;
    }

    $year = (int) substr($digits, 8, 4);
    if ($year < 1950 || $year > (int) date('Y')) {
      return null;
    }

    $month = substr($digits, 12, 2);
    if ($month === '21') {
      return $year . '13';
    }

    return (int) $month >= 1 && (int) $month <= 12 ? $year . $month : null;
  }

  /** Tanggal lahir "YYYYMMDD", atau null bila bukan tanggal yang sah. */
  public static function birthDate(?string $nip): ?string
  {
    $digits = self::digits($nip);
    if ($digits === null) {
      return null;
    }

    $year = (int) substr($digits, 0, 4);
    $valid = $year >= 1930 && $year <= (int) date('Y')
      && checkdate((int) substr($digits, 4, 2), (int) substr($digits, 6, 2), $year);

    return $valid ? substr($digits, 0, 8) : null;
  }

  /** NIP tanpa spasi/tanda baca bila tepat 18 digit, selain itu null. */
  private static function digits(?string $nip): ?string
  {
    $digits = preg_replace('/\D/', '', (string) $nip);

    return strlen($digits) === 18 ? $digits : null;
  }
}
