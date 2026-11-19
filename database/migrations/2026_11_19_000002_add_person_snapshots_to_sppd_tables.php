<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Data orang di SPPD yang sudah disetujui dibekukan (lihat App\Support\PersonSnapshot), agar dokumen
 * & halaman SPPD lama tidak ikut berubah saat pegawai diganti jabatannya atau profilnya diedit:
 *
 *  - sppd_approvals.approver_snapshot: penyetuju/penandatangan, dicatat saat tahapnya bertindak.
 *  - sppd_requests.pelaksana_snapshot & sppd_followers.snapshot: dicatat saat SPPD disetujui penuh.
 *
 * SPPD yang sudah disetujui/selesai diisi oleh `sppd:snapshot-people`: pergantian jabatan, pangkat,
 * dan role dipulihkan dari log aktivitas ke kondisi saat disetujui; nama & NIP, data yang dulu masih
 * kosong, dan nama jabatan di master memakai data sekarang. Keputusan user 2026-11-19.
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::table('sppd_approvals', function (Blueprint $table) {
      $table->json('approver_snapshot')->nullable()->after('approver_id')
        ->comment('Nama/NIP/pangkat/jabatan penyetuju saat bertindak');
    });

    Schema::table('sppd_requests', function (Blueprint $table) {
      $table->json('pelaksana_snapshot')->nullable()->after('user_id')
        ->comment('Nama/NIP/pangkat/jabatan pelaksana saat SPPD disetujui');
    });

    Schema::table('sppd_followers', function (Blueprint $table) {
      $table->json('snapshot')->nullable()->after('user_id')
        ->comment('Nama/NIP/pangkat/jabatan pengikut saat SPPD disetujui');
    });

    Artisan::call('sppd:snapshot-people');
  }

  public function down(): void
  {
    Schema::table('sppd_followers', fn (Blueprint $table) => $table->dropColumn('snapshot'));
    Schema::table('sppd_requests', fn (Blueprint $table) => $table->dropColumn('pelaksana_snapshot'));
    Schema::table('sppd_approvals', fn (Blueprint $table) => $table->dropColumn('approver_snapshot'));
  }
};
