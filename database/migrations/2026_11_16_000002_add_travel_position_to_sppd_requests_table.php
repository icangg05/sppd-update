<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jabatan perjalanan pelaksana (khusus Inspektorat), sama seperti sppd_followers.travel_position:
 * dipilih di modal konfirmasi dan menentukan urutan peserta di SPT.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (! Schema::hasColumn('sppd_requests', 'travel_position')) {
      Schema::table('sppd_requests', function (Blueprint $table) {
        $table->string('travel_position')->nullable()->after('document_number')
          ->comment('Jabatan pelaksana dalam perjalanan (khusus Inspektorat)');
      });
    }
  }

  public function down(): void
  {
    Schema::table('sppd_requests', function (Blueprint $table) {
      $table->dropColumn('travel_position');
    });
  }
};
