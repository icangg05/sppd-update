<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Apakah lama perjalanan dalam daerah menghitung hari Sabtu, dicatat saat SPPD diajukan/diubah
 * sesuai Pengaturan Aplikasi (tipe instansi pelaksana). Null = SPPD lama, saat Sabtu masih
 * dihitung untuk semua instansi (lihat SppdRequest::countsSaturday).
 */
return new class extends Migration
{
  public function up(): void
  {
    if (! Schema::hasColumn('sppd_requests', 'counts_saturday')) {
      Schema::table('sppd_requests', function (Blueprint $table) {
        $table->boolean('counts_saturday')->nullable()->after('domain')
          ->comment('Lama perjalanan dalam daerah menghitung hari Sabtu (null = ya, SPPD lama)');
      });
    }
  }

  public function down(): void
  {
    Schema::table('sppd_requests', function (Blueprint $table) {
      $table->dropColumn('counts_saturday');
    });
  }
};
