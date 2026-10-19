<?php

use App\Models\Position;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Level jabatan sebelumnya seragam 1000. Isi dari role default jabatan
 * (Walikota 1, Sekda 3, Kepala OPD 20, Sekretaris 30, Kabid 40, Kasi 50,
 * selebihnya 1000) agar pengikut di SPT terurut dari jabatan tertinggi.
 */
return new class extends Migration
{
  public function up(): void
  {
    // Hanya yang masih bernilai bawaan — level yang sudah diatur manual tidak ditimpa.
    DB::table('positions')->where('level', Position::DEFAULT_LEVEL)->whereNotNull('default_role')->orderBy('id')
      ->each(function ($position) {
        $level = Position::levelForRole($position->default_role);
        if ($level !== Position::DEFAULT_LEVEL) {
          DB::table('positions')->where('id', $position->id)->update(['level' => $level]);
        }
      });
  }

  public function down(): void
  {
    DB::table('positions')->where('level', '!=', Position::DEFAULT_LEVEL)->whereNotNull('default_role')->orderBy('id')
      ->each(function ($position) {
        if ((int) $position->level === Position::levelForRole($position->default_role)) {
          DB::table('positions')->where('id', $position->id)->update(['level' => Position::DEFAULT_LEVEL]);
        }
      });
  }
};
