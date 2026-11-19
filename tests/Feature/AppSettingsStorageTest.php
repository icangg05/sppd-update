<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Services\AppSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengaturan Aplikasi disimpan di tabel app_settings (sebelumnya data.json). Migrasinya
 * menyalin isi file lama apa adanya.
 */
class AppSettingsStorageTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'migrations/2026_11_18_000003_create_app_settings_table.php';

    public function test_migrasi_menyalin_isi_data_json_lama(): void
    {
        Storage::fake('local')->put('data/json/data.json', json_encode([
            ['key' => 'survey_url', 'value' => 'https://survei.example/embed', 'description' => 'Deskripsi lama'],
            ['key' => 'upload_max_image_mb', 'value' => 7, 'description' => ''],
            ['key' => 'daftar_contoh', 'value' => ['puskesmas', 'rumah_sakit'], 'description' => ''],
            ['key' => 'catatan_manual', 'value' => ['a' => 1], 'description' => 'Ditambah manual'],
            ['value' => 'tanpa key diabaikan'],
        ]));

        Schema::drop('app_settings');
        (require database_path(self::MIGRATION))->up();

        $this->assertSame(4, AppSetting::count());
        $settings = new AppSettings;
        $this->assertSame('https://survei.example/embed', $settings->get('survey_url'));
        $this->assertSame(7 * 1024, $settings->uploadMaxKb(true));
        $this->assertSame(['puskesmas', 'rumah_sakit'], $settings->get('daftar_contoh'));
        // Key yang tidak dikenal kode tetap tampil, dengan deskripsinya.
        $this->assertSame(['a' => 1], $settings->all()['catatan_manual']['value']);
        $this->assertSame('Ditambah manual', $settings->all()['catatan_manual']['description']);
        // Key terdefinisi memakai deskripsi dari kode.
        $this->assertSame(AppSettings::definitions()['survey_url']['description'], $settings->all()['survey_url']['description']);

        // Dijalankan ulang tidak menggandakan atau menimpa baris yang sudah ada.
        (require database_path(self::MIGRATION))->up();
        $this->assertSame(4, AppSetting::count());
    }

    public function test_migrasi_tanpa_file_lama_memakai_nilai_awal(): void
    {
        Storage::fake('local');
        Schema::drop('app_settings');
        (require database_path(self::MIGRATION))->up();

        $this->assertSame(0, AppSetting::count());
        $this->assertSame(10 * 1024, (new AppSettings)->uploadMaxKb(true));
    }

    public function test_membaca_tidak_menulis_dan_menyimpan_hanya_yang_berubah(): void
    {
        $settings = new AppSettings;
        $this->assertSame(50 * 1024, $settings->uploadMaxKb(false));
        // Nilai awal tidak ditulis ke tabel hanya karena dibaca.
        $this->assertSame(0, AppSetting::count());

        $this->assertSame([], $settings->update(['upload_max_file_mb' => 50, 'key_tak_dikenal' => 'x']));
        $this->assertSame(0, AppSetting::count());

        $changes = $settings->update(['upload_max_file_mb' => 20]);
        $this->assertSame(['upload_max_file_mb' => 50], $changes['old']);
        $this->assertSame(1, AppSetting::count());
        $this->assertSame(20, AppSetting::firstWhere('key', 'upload_max_file_mb')->value);
        // Instans baru (request berikutnya) membaca nilai dari tabel.
        $this->assertSame(20 * 1024, (new AppSettings)->uploadMaxKb(false));
    }
}
