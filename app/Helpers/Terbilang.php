<?php

namespace App\Helpers;

class Terbilang
{
  private static array $angka = [
    '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
    'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'
  ];

  public static function convert(float $nilai): string
  {
    $nilai = abs($nilai);

    if ($nilai < 12) {
      return self::$angka[(int)$nilai];
    }
    if ($nilai < 20) {
      return self::$angka[(int)$nilai - 10] . ' Belas';
    }
    if ($nilai < 100) {
      return self::$angka[(int)($nilai / 10)] . ' Puluh ' . self::$angka[(int)($nilai % 10)];
    }
    if ($nilai < 200) {
      return 'Seratus ' . self::convert($nilai - 100);
    }
    if ($nilai < 1000) {
      return self::$angka[(int)($nilai / 100)] . ' Ratus ' . self::convert(fmod($nilai, 100));
    }
    if ($nilai < 2000) {
      return 'Seribu ' . self::convert($nilai - 1000);
    }
    if ($nilai < 1000000) {
      return self::convert($nilai / 1000) . ' Ribu ' . self::convert(fmod($nilai, 1000));
    }
    if ($nilai < 1000000000) {
      return self::convert($nilai / 1000000) . ' Juta ' . self::convert(fmod($nilai, 1000000));
    }
    if ($nilai < 1000000000000) {
      return self::convert($nilai / 1000000000) . ' Miliar ' . self::convert(fmod($nilai, 1000000000));
    }

    return self::convert($nilai / 1000000000000) . ' Triliun ' . self::convert(fmod($nilai, 1000000000000));
  }

  /**
   * Convert number to terbilang with "Rupiah" suffix.
   */
  public static function rupiah(float $nilai): string
  {
    if ($nilai == 0) {
      return 'Nol Rupiah';
    }

    return trim(preg_replace('/\s+/', ' ', self::convert($nilai))) . ' Rupiah';
  }
}
