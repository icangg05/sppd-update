<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('document_signatories', function (Blueprint $table) {
      $table->id();
      $table->foreignId('department_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('position_id')->constrained();
      $table->string('name');
      $table->string('nik')->nullable()->comment('NIK untuk mapping ke e-sign provider');
      $table->string('title')->comment('Jabatan yang tercetak di dokumen');
      $table->string('signature_image')->nullable()->comment('Path gambar tanda tangan basah');
      $table->string('signature_image_path')->nullable()->comment('Path file gambar tanda tangan digital atau cache tanda tangan');
      $table->text('signature_image_notes')->nullable()->comment('Catatan tentang gambar tanda tangan');
      $table->boolean('requires_passphrase')->default(true)->comment('Apakah memerlukan passphrase saat sign');
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('document_signatories');
  }
};
