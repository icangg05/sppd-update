<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('document_signatories', function (Blueprint $table) {
      // Tambahkan kolom untuk TTE
      $table->string('nik')->nullable()->after('name')->comment('NIK untuk mapping ke e-sign provider');
      $table->string('signature_image_path')->nullable()->after('signature_image')->comment('Path file gambar tanda tangan digital atau cache tanda tangan');
      $table->text('signature_image_notes')->nullable()->after('signature_image_path')->comment('Catatan tentang gambar tanda tangan');
      $table->boolean('requires_passphrase')->default(true)->after('signature_image_notes')->comment('Apakah memerlukan passphrase saat sign');
    });
  }

  public function down(): void
  {
    Schema::table('document_signatories', function (Blueprint $table) {
      $table->dropColumn(['nik', 'signature_image_path', 'signature_image_notes', 'requires_passphrase']);
    });
  }
};
