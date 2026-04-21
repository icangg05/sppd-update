<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('budgets', function (Blueprint $table) {
      $table->renameColumn('kegiatan', 'activity');
      $table->renameColumn('kode_rekening', 'account_code');
      $table->renameColumn('uraian', 'description');
    });
  }

  public function down(): void
  {
    Schema::table('budgets', function (Blueprint $table) {
      $table->renameColumn('activity', 'kegiatan');
      $table->renameColumn('account_code', 'kode_rekening');
      $table->renameColumn('description', 'uraian');
    });
  }
};
