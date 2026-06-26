<?php

namespace App\Services;

use App\Models\User;

/**
 * Membuat username yang ringkas, rapi, & mudah diingat dari Nama Lengkap.
 *
 * Format: "depan.belakang" (mis. "Ikrar Anggara Sakra" -> "ikrar.sakra").
 * Gelar/titel (S.T., M.M., Dr., dst.) dibuang lebih dulu. Bila sudah dipakai,
 * cukup ditambah angka mulai dari 2 (ikrar.sakra2, ikrar.sakra3, ...).
 *
 * Dipakai bersama oleh UserForm, Profile, dan command users:generate-usernames.
 */
class UsernameGenerator
{
  /**
   * Username unik untuk sebuah nama. $excludeUserId mengabaikan baris user
   * tertentu saat cek ketersediaan (mis. saat user mengubah datanya sendiri).
   * Mengembalikan null bila nama tidak menghasilkan basis yang valid.
   */
  public function generate(string $name, ?int $excludeUserId = null): ?string
  {
    $base = $this->base($name);
    if ($base === '') {
      return null;
    }

    if (! $this->taken($base, $excludeUserId)) {
      return $base;
    }

    $i = 2;
    while ($this->taken($base . $i, $excludeUserId)) {
      $i++;
    }

    return $base . $i;
  }

  /** Format dasar "depan.belakang" tanpa pengecekan keunikan. */
  public function base(string $name): string
  {
    $parts = $this->nameParts($name);
    if (empty($parts)) {
      return '';
    }

    $first = $parts[0];
    $last = count($parts) > 1 ? end($parts) : '';

    return $last !== '' ? "{$first}.{$last}" : $first;
  }

  /** Pecah nama (tanpa gelar) menjadi kata-kata alfanumerik huruf kecil. */
  protected function nameParts(string $name): array
  {
    $name = strtolower($this->stripAcademicTitles($name));

    return preg_split('/[^a-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
  }

  protected function taken(string $username, ?int $excludeUserId): bool
  {
    return User::where('username', $username)
      ->when($excludeUserId, fn($q) => $q->where('id', '!=', $excludeUserId))
      ->exists();
  }

  /**
   * Membuang gelar/titel dari nama agar tidak ikut ke username.
   * Mis. "Ilmi Faizan, S.T." -> "Ilmi Faizan", "Dr. Ir. Budi, M.M." -> "Budi".
   */
  protected function stripAcademicTitles(string $name): string
  {
    // Gelar belakang lazim dipisah koma: ambil bagian sebelum koma pertama.
    $name = trim(explode(',', $name)[0]);

    // Daftar gelar umum (dinormalkan: huruf kecil tanpa titik).
    $titles = array_flip([
      // Gelar depan
      'dr', 'drs', 'dra', 'ir', 'prof', 'h', 'hj', 'kh', 'hc', 'drg',
      // Gelar belakang
      'st', 'se', 'sh', 'si', 'sos', 'ssos', 'spd', 'skom', 'sip', 'sap',
      'skm', 'sked', 'ssi', 'ss', 'sgz', 'skg', 'spt', 'sptk', 'sfarm', 'sthi',
      'mm', 'mt', 'mkom', 'msi', 'mpd', 'mh', 'msc', 'ma', 'mba', 'mkes',
      'mars', 'mph', 'ba', 'bsc', 'phd', 'amd', 'amk', 'ners', 'sps', 'spog',
    ]);

    $tokens = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $kept = [];
    foreach ($tokens as $token) {
      // Token gelar dikenali bila mengandung titik (mis. "S.T.") atau cocok daftar.
      $normalized = strtolower(preg_replace('/[^a-z]/i', '', $token));
      if ($normalized === '' || str_contains($token, '.') || isset($titles[$normalized])) {
        continue;
      }
      $kept[] = $token;
    }

    // Bila semua token terbuang (mis. nama cuma berisi gelar), pakai nama asli.
    return $kept ? implode(' ', $kept) : $name;
  }
}
