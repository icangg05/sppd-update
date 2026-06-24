<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('positions', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->integer('level')->default(1000)->comment('Urutan jabatan, semakin kecil semakin tinggi');
      $table->string('uniqueness_scope')->default('none')
        ->comment('Batas jumlah pemangku jabatan: none, department, system');
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('positions');
  }
};
