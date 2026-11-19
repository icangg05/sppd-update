<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Support\FileSize;
use Illuminate\Support\Facades\DB;

/**
 * Pengaturan aplikasi yang bisa diubah Super Admin tanpa menyentuh .env.
 *
 * Disimpan di tabel app_settings (satu baris per key, nilai JSON), sehingga ikut Backup
 * Database, ikut terkirim bersama database, dan sama untuk semua container (app, queue,
 * scheduler). Sampai 2026-11-18 disimpan di storage/app/private/data/json/data.json; isinya
 * disalin oleh migrasi create_app_settings_table.
 *
 * Key yang dipakai kode didefinisikan di definitions(), lengkap dengan nilai awal, deskripsi,
 * dan aturan validasi. Key yang belum pernah disimpan memakai nilai awalnya (tidak ditulis ke
 * tabel saat dibaca). Key lain yang ada di tabel tetap dipertahankan dan ikut tampil.
 *
 * Dibaca sekali per request/job (binding scoped di AppServiceProvider). Sengaja tanpa Cache:
 * cache store `file` tidak dibagi antar-container, jadi queue/scheduler bisa membaca nilai lama.
 * Konfigurasi yang berbeda per lingkungan atau rahasia (API key, URL) tetap di .env/config.
 */
class AppSettings
{
  /** @var array<string, array{key: string, value: mixed, description: string}>|null */
  private ?array $items = null;

  /**
   * Key yang dikenal kode: label tampilan, deskripsi, nilai awal, dan aturan validasi.
   * `options` = pengaturan berupa daftar centang (nilai tersimpan: daftar key opsi yang dicentang).
   */
  public static function definitions(): array
  {
    return [
      'survey_url' => [
        'label'       => 'Link Survei Kepuasan',
        'description' => 'Link embed Survei Kepuasan Masyarakat (Survei Digital SPBE) yang dibuka dari halaman login.',
        'default'     => config('app.survey_url'),
        'rules'       => ['required', 'url', 'max:2000'],
      ],
      // Batas atas 100 MB mengikuti upload_max_filesize/post_max_size di docker/php.ini.
      'upload_max_image_mb' => [
        'label'       => 'Maks. Upload Gambar (MB)',
        'description' => 'Ukuran maksimal foto/gambar yang diunggah (foto dokumentasi, nota, dokumen pendukung bergambar, kop surat). Gambar dikompres otomatis setelah diunggah. Maksimal 100.',
        'default'     => 10,
        'rules'       => ['required', 'integer', 'min:1', 'max:100'],
      ],
      'upload_max_file_mb' => [
        'label'       => 'Maks. Upload Berkas (MB)',
        'description' => 'Ukuran maksimal berkas selain gambar (PDF, DOC, DOCX) seperti file laporan perjalanan dan dokumen pendukung. Maksimal 100.',
        'default'     => 50,
        'rules'       => ['required', 'integer', 'min:1', 'max:100'],
      ],
    ];
  }

  /**
   * Batas ukuran unggahan dalam KB (untuk aturan validasi `max:`), gambar atau berkas lain.
   * Nilai di file yang bukan bilangan bulat positif memakai nilai awal.
   */
  public function uploadMaxKb(bool $image): int
  {
    $key = $image ? 'upload_max_image_mb' : 'upload_max_file_mb';
    $mb  = filter_var($this->get($key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return ($mb ?: self::definitions()[$key]['default']) * 1024;
  }

  /** Teks batas ukuran untuk petunjuk di bawah input, mis. "10 MB". */
  public function uploadMaxLabel(bool $image): string
  {
    return FileSize::fromKilobytes($this->uploadMaxKb($image));
  }

  /**
   * Semua pengaturan, key terdefinisi lebih dulu sesuai urutan definitions().
   *
   * @return array<string, array{key: string, value: mixed, description: string}>
   */
  public function all(): array
  {
    return $this->items ??= $this->load();
  }

  public function get(string $key, mixed $default = null): mixed
  {
    return $this->all()[$key]['value'] ?? $default;
  }

  /**
   * Simpan nilai baru untuk key yang sudah ada. Mengembalikan perubahan
   * dalam bentuk ['old' => [...], 'attributes' => [...]] (kosong bila tidak ada).
   */
  public function update(array $values): array
  {
    $items   = $this->all();
    $changes = ['old' => [], 'attributes' => []];

    foreach ($values as $key => $value) {
      if (! isset($items[$key]) || $items[$key]['value'] === $value) {
        continue;
      }

      $changes['old'][$key]        = $items[$key]['value'];
      $changes['attributes'][$key] = $value;
      $items[$key]['value']        = $value;
    }

    if ($changes['attributes'] !== []) {
      $this->write($changes['attributes']);
      $this->items = $items;
    }

    return $changes['attributes'] === [] ? [] : $changes;
  }

  private function load(): array
  {
    $stored = AppSetting::query()->get()->keyBy('key');

    // Key terdefinisi selalu memakai deskripsi dari kode, agar perubahan teksnya langsung tampil.
    $items = [];
    foreach (self::definitions() as $key => $def) {
      $row = $stored->pull($key);
      $items[$key] = [
        'key'         => $key,
        'value'       => $row ? $row->value : $def['default'],
        'description' => $def['description'],
      ];
    }

    foreach ($stored as $key => $row) {
      $items[$key] = [
        'key'         => $key,
        'value'       => $row->value,
        'description' => (string) $row->description,
      ];
    }

    return $items;
  }

  /** Simpan nilai yang berubah (baris dibuat bila key belum pernah disimpan). */
  private function write(array $values): void
  {
    DB::transaction(function () use ($values) {
      foreach ($values as $key => $value) {
        AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
      }
    });
  }
}
