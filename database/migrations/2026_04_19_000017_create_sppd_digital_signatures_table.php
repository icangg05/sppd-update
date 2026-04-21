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
