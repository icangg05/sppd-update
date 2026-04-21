<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('sppd_destinations', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sppd_request_id')->constrained()->cascadeOnDelete();
      $table->foreignId('province_id')->constrained();
      $table->foreignId('regency_id')->nullable()->constrained()->nullOnDelete();
      $table->text('address')->nullable()->comment('Alamat detail tujuan');
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('sppd_destinations');
  }
};
