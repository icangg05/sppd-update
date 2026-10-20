<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Pengaturan aplikasi yang bisa diubah Super Admin tanpa menyentuh .env.
 *
 * Disimpan di storage/app/private/data/json/data.json sebagai daftar
 * `{ key, value, description }`. Key yang dipakai kode didefinisikan di
 * definitions(); nilai awalnya diambil dari config sehingga file boleh belum
 * ada — file dibuat otomatis saat pertama dibaca. Key tambahan yang ditulis
 * manual ke file tetap dipertahankan dan ikut tampil di halaman pengaturan.
 */
class AppSettings
{
  /** Disk privat (storage/app/private) — tidak bisa diakses lewat URL publik. */
  public const DISK = 'local';
  public const PATH = 'data/json/data.json';

  /** @var array<string, array{key: string, value: mixed, description: string}>|null */
  private ?array $items = null;

  /**
   * Key yang dikenal kode: label tampilan, deskripsi, nilai awal, dan aturan validasi.
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
    ];
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
      $this->write($items);
    }

    return $changes['attributes'] === [] ? [] : $changes;
  }

  private function load(): array
  {
    $disk   = Storage::disk(self::DISK);
    $exists = $disk->exists(self::PATH);
    $raw    = $exists ? json_decode((string) $disk->get(self::PATH), true) : null;

    // File ada tapi JSON-nya rusak (mis. salah edit manual): pakai nilai awal
    // tanpa menimpa file, supaya isinya masih bisa diperbaiki.
    $corrupt = $exists && ! is_array($raw);
    if ($corrupt) {
      logger()->warning('Pengaturan aplikasi: ' . self::PATH . ' bukan JSON yang valid, memakai nilai awal.');
    }

    // Isi file diindeks per key; entri tanpa key yang valid diabaikan.
    $stored = collect(is_array($raw) ? $raw : [])
      ->filter(fn ($row) => is_array($row) && is_string($row['key'] ?? null) && $row['key'] !== '')
      ->keyBy('key');

    $missing = array_diff_key(self::definitions(), $stored->all());

    $items = [];
    foreach (self::definitions() as $key => $def) {
      $row = $stored->pull($key);
      $items[$key] = [
        'key'         => $key,
        'value'       => $row !== null && array_key_exists('value', $row) ? $row['value'] : $def['default'],
        'description' => filled($row['description'] ?? null) ? $row['description'] : $def['description'],
      ];
    }

    foreach ($stored as $key => $row) {
      $items[$key] = [
        'key'         => $key,
        'value'       => $row['value'] ?? null,
        'description' => (string) ($row['description'] ?? ''),
      ];
    }

    // Buat file bila belum ada, atau lengkapi bila ada key terdefinisi yang belum tercatat.
    if (! $corrupt && $missing !== []) {
      $this->write($items);
    }

    return $items;
  }

  private function write(array $items): void
  {
    Storage::disk(self::DISK)->put(
      self::PATH,
      json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
    );

    $this->items = $items;
  }
}
