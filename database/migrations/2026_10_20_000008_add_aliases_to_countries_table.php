<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nama lain negara sebagai kata kunci pencarian dropdown (mis. "Cina"/"China"
 * menemukan "Tiongkok"). Nama resmi di kolom `name` tetap yang tercetak di dokumen.
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::table('countries', function (Blueprint $table) {
      $table->string('aliases', 255)->nullable()->after('name')
        ->comment('Nama lain dipisah koma, hanya untuk pencarian');
    });

    $aliases = require database_path('data/country_aliases.php');
    foreach ($aliases as $iso2 => $value) {
      DB::table('countries')->where('iso2', $iso2)->whereNull('aliases')->update(['aliases' => $value]);
    }
  }

  public function down(): void
  {
    Schema::table('countries', function (Blueprint $table) {
      $table->dropColumn('aliases');
    });
  }
};
