<?php

namespace App\Support;

/**
 * Ukuran berkas yang mudah dibaca dari nilai kilobita (satuan aturan validasi max/min/size),
 * mis. 10240 → "10 MB", 1536 → "1,5 MB", 500 → "500 KB".
 */
class FileSize
{
  public static function fromKilobytes(int|float|string $kilobytes): string
  {
    $kilobytes = (float) $kilobytes;

    if ($kilobytes < 1024) {
      return number_format($kilobytes, 0, ',', '.') . ' KB';
    }

    // Satu angka desimal, tanpa ",0" di belakang.
    return rtrim(rtrim(number_format($kilobytes / 1024, 1, ',', '.'), '0'), ',') . ' MB';
  }
}
