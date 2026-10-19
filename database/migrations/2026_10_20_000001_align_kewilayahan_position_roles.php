<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Workflow memperlakukan kelurahan, kecamatan, dan puskesmas sebagai OPD
 * mandiri (alur Staf: Sekretaris OPD → Kepala OPD). Role default jabatan
 * kepala & sekretarisnya disamakan agar penyetuju bisa ditemukan.
 */
return new class extends Migration
{
  // Nama jabatan (lowercase) => [role baru, role lama].
  private const MAP = [
    'camat'                 => ['kepala_opd', 'camat'],
    'lurah'                 => ['kepala_opd', 'lurah'],
    'kepala puskesmas'      => ['kepala_opd', 'kapus'],
    'kepala uptd puskesmas' => ['kepala_opd', 'kapus'],
    'sekretaris camat'      => ['sekretaris_opd', 'sekcam'],
    'sekretaris lurah'      => ['sekretaris_opd', null],
    'sekretaris kelurahan'  => ['sekretaris_opd', null],
  ];

  public function up(): void
  {
    foreach (self::MAP as $name => [$new, $old]) {
      // Hanya ubah yang masih bernilai bawaan lama — pengaturan Super Admin tidak ditimpa.
      DB::table('positions')
        ->whereRaw('LOWER(name) = ?', [$name])
        ->where(fn ($q) => $old === null ? $q->whereNull('default_role') : $q->where('default_role', $old))
        ->update(['default_role' => $new]);
    }
  }

  public function down(): void
  {
    foreach (self::MAP as $name => [$new, $old]) {
      DB::table('positions')
        ->whereRaw('LOWER(name) = ?', [$name])
        ->where('default_role', $new)
        ->update(['default_role' => $old]);
    }
  }
};
