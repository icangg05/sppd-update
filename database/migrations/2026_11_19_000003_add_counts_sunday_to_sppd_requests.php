<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perjalanan dalam daerah: hari Sabtu dan Minggu dihitung atau tidak dipilih per SPPD oleh pembuatnya
 * (centang di form; Sabtu awalnya tercentang untuk Puskesmas & Rumah Sakit). Keputusan user 2026-11-19,
 * karena kebiasaan OPD tidak bergantung pada tipe instansi.
 *
 *  - sppd_requests.counts_sunday: null = SPPD lama = Minggu tidak dihitung (aturan sebelumnya).
 *    counts_saturday tetap; null = SPPD lama = Sabtu dihitung. Lama hari SPPD lama tidak berubah.
 *  - Pengaturan Aplikasi "Sabtu Dihitung (Dalam Daerah)" per tipe instansi tidak dipakai lagi.
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::table('sppd_requests', function (Blueprint $table) {
      $table->boolean('counts_sunday')->nullable()->after('counts_saturday')
        ->comment('Dalam daerah: hari Minggu dihitung? null = SPPD lama (tidak dihitung)');
    });

    if (Schema::hasTable('app_settings')) {
      DB::table('app_settings')->where('key', 'dalam_daerah_sabtu_types')->delete();
    }
  }

  public function down(): void
  {
    Schema::table('sppd_requests', fn (Blueprint $table) => $table->dropColumn('counts_sunday'));
  }
};
