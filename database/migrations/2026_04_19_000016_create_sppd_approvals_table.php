<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('sppd_approvals', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sppd_request_id')->constrained()->cascadeOnDelete();
      $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
      $table->string('role_label')->comment('Label jabatan penyetuju, misal: Kasubag, Sekda, Walikota');
      $table->unsignedTinyInteger('step_order')->comment('Urutan langkah persetujuan');
      $table->string('status')->default('pending')->comment('pending, approved, rejected, revision');
      $table->timestamp('acted_at')->nullable();
      $table->text('notes')->nullable();
      $table->timestamps();

      $table->index(['sppd_request_id', 'step_order']);
      $table->index('status');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('sppd_approvals');
  }
};
