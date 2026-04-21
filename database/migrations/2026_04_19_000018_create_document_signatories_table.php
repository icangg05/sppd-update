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
      $table->string('title')->comment('Jabatan yang tercetak di dokumen');
      $table->string('signature_image')->nullable()->comment('Path gambar tanda tangan basah');
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('document_signatories');
  }
};
