<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('departments', function (Blueprint $table) {
      // Bila false, instansi ini tidak melihat data unit di bawahnya —
      // tiap anak menjadi zona data tersendiri (independen dua arah).
      $table->boolean('can_view_children_data')->default(true)->after('level');
    });
  }

  public function down(): void
  {
    Schema::table('departments', function (Blueprint $table) {
      $table->dropColumn('can_view_children_data');
    });
  }
};
