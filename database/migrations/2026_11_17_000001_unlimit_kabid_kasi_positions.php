<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Jabatan tingkat Kabid & Kasi tidak lagi dibatasi satu pemangku per OPD; batas itu
 * kini hanya untuk Pimpinan & Sekretaris (lihat PositionTier::scope). Tahap Kabid
 * dicari lewat atasan langsung pemohon, jadi keunikan jabatannya tidak dipakai alur.
 */
return new class extends Migration
{
  // Role ditulis langsung agar migrasi tidak ikut berubah bila enum/konfigurasi diganti.
  private const ROLES = ['kabid_irban_kabag', 'kasubid_kasubag'];

  public function up(): void
  {
    DB::table('positions')
      ->whereIn('default_role', self::ROLES)
      ->where('uniqueness_scope', 'department')
      ->update(['uniqueness_scope' => 'none']);
  }

  public function down(): void
  {
    DB::table('positions')
      ->whereIn('default_role', self::ROLES)
      ->where('uniqueness_scope', 'none')
      ->update(['uniqueness_scope' => 'department']);
  }
};
