<?php

namespace App\Support;

/**
 * Folder unggahan per tahun di disk public: dokumen-{tahun}/{jenis},
 * mis. dokumen-2026/dokumen-pendukung. Tahun mengikuti saat berkas disimpan.
 * Nama folder memakai tanda hubung, bukan garis bawah.
 */
class DocumentFolder
{
  public const PREFIX = 'dokumen-';

  public static function path(string $subfolder, ?int $year = null): string
  {
    return self::PREFIX . ($year ?? (int) date('Y')) . '/' . $subfolder;
  }
}
