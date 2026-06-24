<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('positions', function (Blueprint $table) {
      $table->string('uniqueness_scope')->default('none')->after('level')
        ->comment('Batas jumlah pemangku jabatan: none, department, system');
    });
  }

  public function down(): void
  {
    Schema::table('positions', function (Blueprint $table) {
      $table->dropColumn('uniqueness_scope');
    });
  }
};
