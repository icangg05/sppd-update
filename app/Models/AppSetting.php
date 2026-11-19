<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris Pengaturan Aplikasi (lihat App\Services\AppSettings). Nilai disimpan sebagai JSON
 * agar teks, angka, dan daftar (mis. tipe instansi) tetap bertipe aslinya saat dibaca.
 * Baca & ubah lewat AppSettings, bukan model ini langsung, agar nilai awal dan validasinya berlaku.
 */
class AppSetting extends Model
{
  protected $fillable = ['key', 'value', 'description'];

  protected function casts(): array
  {
    return [
      'value' => 'json',
    ];
  }
}
