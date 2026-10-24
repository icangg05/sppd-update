<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tipe status pegawai (PNS/PPPK/Honorer/Lainnya) hanya informasi tanpa fungsi.
 * Penanda Anggota DPRD kini dari role (anggota_dprd / pimpinan_dprd), yang sudah
 * dimiliki semua pegawai bertipe DPRD. Log aktivitas lama tetap terbaca.
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn('employee_type');
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('employee_type')->default('pns')->after('changelog_seen_version');
    });

    // Pulihkan penanda DPRD dari role; tipe lain kembali ke bawaan (PNS).
    DB::table('users')
      ->whereIn('id', DB::table('model_has_roles')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->whereIn('roles.name', ['anggota_dprd', 'pimpinan_dprd'])
        ->select('model_has_roles.model_id'))
      ->update(['employee_type' => 'dprd']);
  }
};
