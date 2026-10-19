<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master negara & kota tujuan perjalanan dinas luar negeri (sebelumnya daftar
 * negara di config/countries.php, kota diketik bebas).
 *
 * Data awal dari database/data/:
 * - countries.php      : kode ISO2 => nama negara (Bahasa Indonesia).
 * - foreign_cities.csv : kota berpenduduk ≥ 100 ribu ditambah ibu kota negara &
 *                        ibu kota provinsi/negara bagian, dari GeoNames
 *                        (https://www.geonames.org, lisensi CC BY 4.0).
 *
 * sppd_destinations.country/city tetap menyimpan teks nama, jadi master ini
 * hanya sumber pilihan di form — data SPPD lama tidak terpengaruh.
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::create('countries', function (Blueprint $table) {
      $table->id();
      $table->char('iso2', 2)->nullable()->unique()->comment('Kode ISO 3166-1 alpha-2');
      $table->string('name', 100)->unique();
      $table->timestamps();
    });

    Schema::create('foreign_cities', function (Blueprint $table) {
      $table->id();
      $table->foreignId('country_id')->constrained()->cascadeOnDelete();
      $table->string('name', 150);
      $table->timestamps();

      $table->unique(['country_id', 'name']);
    });

    $this->seed();
  }

  public function down(): void
  {
    Schema::dropIfExists('foreign_cities');
    Schema::dropIfExists('countries');
  }

  private function seed(): void
  {
    $now = now();

    $countries = require database_path('data/countries.php');
    DB::table('countries')->insert(collect($countries)
      ->map(fn ($name, $iso2) => ['iso2' => $iso2, 'name' => $name, 'created_at' => $now, 'updated_at' => $now])
      ->values()
      ->all());

    $countryIds = DB::table('countries')->pluck('id', 'iso2');

    $file = fopen(database_path('data/foreign_cities.csv'), 'r');
    fgetcsv($file); // header: iso2,name

    $rows = [];
    while (($line = fgetcsv($file)) !== false) {
      [$iso2, $name] = $line;
      if (! isset($countryIds[$iso2])) {
        continue;
      }

      $rows[] = ['country_id' => $countryIds[$iso2], 'name' => $name, 'created_at' => $now, 'updated_at' => $now];
      if (count($rows) === 500) {
        DB::table('foreign_cities')->insert($rows);
        $rows = [];
      }
    }
    fclose($file);

    if ($rows) {
      DB::table('foreign_cities')->insert($rows);
    }
  }
};
