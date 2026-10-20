<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Urutan unit di antara saudara seinduk (diatur lewat seret-lepas di halaman
   * struktur). Default 0: urutan lama (abjad) tetap berlaku sampai disusun ulang.
   */
  public function up(): void
  {
    Schema::table('departments', function (Blueprint $table) {
      $table->unsignedInteger('sort_order')->default(0)->after('level');
    });
  }

  public function down(): void
  {
    Schema::table('departments', function (Blueprint $table) {
      $table->dropColumn('sort_order');
    });
  }
};
