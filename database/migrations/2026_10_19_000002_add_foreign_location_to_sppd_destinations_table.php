<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('sppd_destinations', function (Blueprint $table) {
      $table->string('country', 100)->nullable()->after('regency_id')
        ->comment('Negara tujuan (khusus domain luar_negeri)');
      $table->string('city', 150)->nullable()->after('country')
        ->comment('Kota tujuan (khusus domain luar_negeri)');
    });
  }

  public function down(): void
  {
    Schema::table('sppd_destinations', function (Blueprint $table) {
      $table->dropColumn(['country', 'city']);
    });
  }
};
