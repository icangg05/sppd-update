<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('sppd_digital_signatures', function (Blueprint $table) {
      // Tambahkan kolom baru untuk TTE
      $table->string('document_type')->default('sppd')->after('status')->comment('sppd, spt, kuitansi');
      $table->string('provider_id')->nullable()->after('document_type')->comment('ID dokumen dari API provider');
      $table->text('error_message')->nullable()->after('provider_id')->comment('Pesan error jika signing gagal');
      $table->string('signed_file_path')->nullable()->after('error_message')->comment('Path file PDF yang sudah di-sign');
      $table->text('qr_code_data')->nullable()->after('signed_file_path')->comment('Data QR code yang di-embed');
      $table->integer('sign_page')->default(1)->after('qr_code_data')->comment('Halaman penandatangan');
      $table->integer('sign_x')->default(220)->after('sign_page')->comment('Koordinat X penandatangan (px)');
      $table->integer('sign_y')->default(100)->after('sign_x')->comment('Koordinat Y penandatangan (px)');
      $table->integer('sign_width')->default(545)->after('sign_y')->comment('Lebar area tanda tangan (px)');
      $table->integer('sign_height')->default(130)->after('sign_width')->comment('Tinggi area tanda tangan (px)');
      $table->string('provider_name')->default('local_proxy')->after('sign_height')->comment('Nama provider: local_proxy, bssn, dll');
    });
  }

  public function down(): void
  {
    Schema::table('sppd_digital_signatures', function (Blueprint $table) {
      $table->dropColumn([
        'document_type',
        'provider_id',
        'error_message',
        'signed_file_path',
        'qr_code_data',
        'sign_page',
        'sign_x',
        'sign_y',
        'sign_width',
        'sign_height',
        'provider_name',
      ]);
    });
  }
};
