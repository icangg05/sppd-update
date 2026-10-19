<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Jabatan pimpinan Badan yang belum ada di master: Kepala Bappeda dan
 * Sekretaris Badan (sekretaris di semua Badan).
 */
return new class extends Migration
{
  private const POSITIONS = [
    'Kepala Badan Perencanaan Pembangunan Daerah' => 'kepala_opd',
    'Sekretaris Badan' => 'sekretaris_opd',
  ];

  public function up(): void
  {
    // Instalasi baru mendapat jabatan ini dari PositionAndRankSeeder.
    if (DB::table('positions')->doesntExist()) {
      return;
    }

    foreach (self::POSITIONS as $name => $role) {
      $exists = DB::table('positions')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists();
      if ($exists) {
        continue;
      }

      DB::table('positions')->insert([
        'name' => $name,
        'level' => 1000,
        'uniqueness_scope' => 'department',
        'default_role' => $role,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }
  }

  public function down(): void
  {
    foreach (array_keys(self::POSITIONS) as $name) {
      $position = DB::table('positions')->where('name', $name)->whereNull('created_by')->first();

      if ($position && ! DB::table('users')->where('position_id', $position->id)->exists()) {
        DB::table('positions')->where('id', $position->id)->delete();
      }
    }
  }
};
