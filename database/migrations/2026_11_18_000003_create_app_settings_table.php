<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Pengaturan Aplikasi pindah dari storage/app/private/data/json/data.json ke tabel ini, agar ikut
 * Backup Database, ikut terkirim bersama database, dan dipakai bersama semua container.
 * Isi file lama disalin apa adanya; filenya dibiarkan (tidak dipakai lagi) sebagai cadangan.
 */
return new class extends Migration
{
  /** Lokasi lama di disk 'local' (storage/app/private). */
  private const LEGACY_PATH = 'data/json/data.json';

  public function up(): void
  {
    if (! Schema::hasTable('app_settings')) {
      Schema::create('app_settings', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->json('value')->nullable();
        $table->text('description')->nullable()
          ->comment('Hanya untuk key yang tidak didefinisikan kode; key terdefinisi memakai deskripsi di AppSettings::definitions()');
        $table->timestamps();
      });
    }

    $this->importLegacyFile();
  }

  public function down(): void
  {
    Schema::dropIfExists('app_settings');
  }

  private function importLegacyFile(): void
  {
    $disk = Storage::disk('local');
    if (! $disk->exists(self::LEGACY_PATH)) {
      return;
    }

    $rows = json_decode((string) $disk->get(self::LEGACY_PATH), true);
    if (! is_array($rows)) {
      logger()->warning('Pengaturan aplikasi: ' . self::LEGACY_PATH . ' bukan JSON yang valid, tidak disalin ke tabel app_settings.');
      return;
    }

    $now = now();
    foreach ($rows as $row) {
      $key = is_array($row) ? ($row['key'] ?? null) : null;
      if (! is_string($key) || $key === '' || DB::table('app_settings')->where('key', $key)->exists()) {
        continue;
      }

      DB::table('app_settings')->insert([
        'key'         => $key,
        'value'       => array_key_exists('value', $row) && $row['value'] !== null
          ? json_encode($row['value'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
          : null,
        'description' => filled($row['description'] ?? null) ? (string) $row['description'] : null,
        'created_at'  => $now,
        'updated_at'  => $now,
      ]);
    }
  }
};
