<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Atasan langsung pegawai. Rantai atasan (Staf → Kabag → Asisten) menentukan
 * pejabat tahap persetujuan tanpa harus menyusun sub-unit kerja lebih dulu.
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->foreignId('atasan_id')->nullable()->after('position_id')
        ->constrained('users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropConstrainedForeignId('atasan_id');
    });
  }
};
