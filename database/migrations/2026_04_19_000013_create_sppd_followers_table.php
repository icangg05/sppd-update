<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('sppd_followers', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sppd_request_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('sppd_path')->nullable()->comment('Path file SPPD Pengikut PDF');
      $table->text('notes')->nullable();
      $table->string('travel_position')->nullable()->comment('Jabatan dalam perjalanan (khusus Inspektorat)');
      $table->timestamps();

      $table->unique(['sppd_request_id', 'user_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('sppd_followers');
  }
};
