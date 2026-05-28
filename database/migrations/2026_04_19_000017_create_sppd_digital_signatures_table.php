<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('sppd_digital_signatures', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sppd_request_id')->constrained()->cascadeOnDelete();
      $table->foreignId('signer_id')->constrained('users')->cascadeOnDelete();
      $table->string('status')->default('pending')->comment('pending, signed, rejected');
      $table->string('document_type')->default('sppd')->comment('sppd, spt, kuitansi');
      $table->string('provider_id')->nullable()->comment('ID dokumen dari API provider');
      $table->text('error_message')->nullable()->comment('Pesan error jika signing gagal');
      $table->string('signed_file_path')->nullable()->comment('Path file PDF yang sudah di-sign');
      $table->text('qr_code_data')->nullable()->comment('Data QR code yang di-embed');
      $table->integer('sign_page')->default(1)->comment('Halaman penandatangan');
      $table->integer('sign_x')->default(220)->comment('Koordinat X penandatangan (px)');
      $table->integer('sign_y')->default(100)->comment('Koordinat Y penandatangan (px)');
      $table->integer('sign_width')->default(545)->comment('Lebar area tanda tangan (px)');
      $table->integer('sign_height')->default(130)->comment('Tinggi area tanda tangan (px)');
      $table->string('provider_name')->default('local_proxy')->comment('Nama provider: local_proxy, bssn, dll');
      $table->timestamp('signed_at')->nullable();
      $table->text('signature_data')->nullable()->comment('Data tanda tangan digital atau hash');
      $table->string('certificate_serial')->nullable()->comment('Nomor sertifikat elektronik');
      $table->timestamps();

      $table->index('status');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('sppd_digital_signatures');
  }
};
