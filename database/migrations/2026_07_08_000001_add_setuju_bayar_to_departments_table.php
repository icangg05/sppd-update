<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('departments', function (Blueprint $table) {
      $table->foreignId('setuju_bayar_user_id')->nullable()->after('head_id')
        ->comment('Penandatangan Setuju Bayar pada dokumen cetak; null = fallback kepala_opd')
        ->constrained('users')->nullOnDelete();
      $table->string('setuju_bayar_label', 150)->nullable()->after('setuju_bayar_user_id')
        ->comment('Label jabatan penandatangan Setuju Bayar; null = label bawaan');
    });
  }

  public function down(): void
  {
    Schema::table('departments', function (Blueprint $table) {
      $table->dropConstrainedForeignId('setuju_bayar_user_id');
      $table->dropColumn('setuju_bayar_label');
    });
  }
};
