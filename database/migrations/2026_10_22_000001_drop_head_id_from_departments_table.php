<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pimpinan unit tidak dipakai lagi: pejabat tahap persetujuan ditentukan dari role
 * pegawai dan atasan langsung (users.atasan_id). Log aktivitas lama tetap terbaca.
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::table('departments', function (Blueprint $table) {
      $table->dropConstrainedForeignId('head_id');
    });
  }

  public function down(): void
  {
    Schema::table('departments', function (Blueprint $table) {
      $table->foreignId('head_id')->nullable()->after('parent_id')->constrained('users')->nullOnDelete();
    });
  }
};
