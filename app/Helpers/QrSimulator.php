<?php

namespace App\Helpers;

/**
 * Simulasi QR Code untuk dokumen SPPD/SPT.
 *
 * Menghasilkan gambar QR-like sebagai base64 PNG menggunakan GD.
 * Untuk produksi, ganti dengan library seperti simplesoftwareio/simple-qrcode atau BSrE API.
 */
class QrSimulator
{
  /**
   * Generate a simulated QR code as base64 PNG data URI.
   *
   * @param string $data Teks/URL yang akan di-encode
   * @param int $size Ukuran gambar dalam pixel
   * @return string Base64 data URI (data:image/png;base64,...)
   */
  public static function generate(string $data, int $size = 150): string
  {
    $moduleCount = 25; // jumlah modul (kotak kecil)
    $moduleSize  = (int) floor($size / ($moduleCount + 8)); // +8 untuk quiet zone
    $quietZone   = $moduleSize * 4;
    $imgSize     = $moduleCount * $moduleSize + $quietZone * 2;

    $img = imagecreatetruecolor($imgSize, $imgSize);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);

    // Background putih
    imagefill($img, 0, 0, $white);

    // Generate pattern berdasarkan hash data (deterministik)
    $hash = hash('sha256', $data);
    $bits = '';
    for ($i = 0; $i < strlen($hash); $i++) {
      $bits .= str_pad(base_convert($hash[$i], 16, 2), 4, '0', STR_PAD_LEFT);
    }

    // Extend bits jika kurang
    while (strlen($bits) < $moduleCount * $moduleCount) {
      $bits .= $bits;
    }

    // Draw finder patterns (tiga kotak besar di sudut)
    self::drawFinderPattern($img, $black, $white, $quietZone, $quietZone, $moduleSize);
    self::drawFinderPattern($img, $black, $white, $quietZone + ($moduleCount - 7) * $moduleSize, $quietZone, $moduleSize);
    self::drawFinderPattern($img, $black, $white, $quietZone, $quietZone + ($moduleCount - 7) * $moduleSize, $moduleSize);

    // Draw data modules
    for ($row = 0; $row < $moduleCount; $row++) {
      for ($col = 0; $col < $moduleCount; $col++) {
        // Skip area finder pattern
        if (self::isInFinderPattern($row, $col, $moduleCount)) {
          continue;
        }

        $bitIndex = ($row * $moduleCount + $col) % strlen($bits);
        if ($bits[$bitIndex] === '1') {
          $x = $quietZone + $col * $moduleSize;
          $y = $quietZone + $row * $moduleSize;
          imagefilledrectangle($img, $x, $y, $x + $moduleSize - 1, $y + $moduleSize - 1, $black);
        }
      }
    }

    // Capture output sebagai PNG
    ob_start();
    imagepng($img);
    $pngData = ob_get_clean();
    imagedestroy($img);

    return 'data:image/png;base64,' . base64_encode($pngData);
  }

  /**
   * Draw a 7x7 finder pattern.
   */
  private static function drawFinderPattern($img, $black, $white, int $x, int $y, int $ms): void
  {
    // Outer black border
    imagefilledrectangle($img, $x, $y, $x + 7 * $ms - 1, $y + 7 * $ms - 1, $black);
    // Inner white
    imagefilledrectangle($img, $x + $ms, $y + $ms, $x + 6 * $ms - 1, $y + 6 * $ms - 1, $white);
    // Center black
    imagefilledrectangle($img, $x + 2 * $ms, $y + 2 * $ms, $x + 5 * $ms - 1, $y + 5 * $ms - 1, $black);
  }

  /**
   * Check if a module position is within a finder pattern area.
   */
  private static function isInFinderPattern(int $row, int $col, int $count): bool
  {
    // Top-left
    if ($row < 8 && $col < 8) return true;
    // Top-right
    if ($row < 8 && $col >= $count - 8) return true;
    // Bottom-left
    if ($row >= $count - 8 && $col < 8) return true;

    return false;
  }
}
