<?php

namespace App\Helpers;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * QR Code Generator menggunakan library simplesoftwareio/simple-qrcode.
 */
class QrSimulator
{
  /**
   * Generate a real QR code as base64 SVG data URI.
   *
   * @param string $data Teks/URL yang akan di-encode
   * @param int $size Ukuran gambar
   * @return string Base64 data URI (data:image/svg+xml;base64,...)
   */
  public static function generate(string $data, int $size = 60): string
  {
    $qr = QrCode::size($size)->errorCorrection('H')->generate($data);
    return 'data:image/svg+xml;base64,' . base64_encode($qr);
  }
}
