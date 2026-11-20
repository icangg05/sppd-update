<?php

namespace App\Enums;

use App\Models\Position;
use App\Models\User;

/**
 * Tingkat jabatan dalam bahasa awam — dipakai Admin OPD saat menambah jabatan
 * agar tidak perlu memahami nama role teknis. Tidak disimpan ke database:
 * setiap tingkat menurunkan role default & cakupan keunikan jabatan.
 */
enum PositionTier: string
{
  case PIMPINAN = 'pimpinan';
  case SEKRETARIS = 'sekretaris';
  case KABID = 'kabid';
  case KASI = 'kasi';
  case STAF = 'staf';
  // Jabatan Tenaga Mitra (pengikut dari luar OPD): tanpa tingkat, hanya dibuat dari
  // mode Tenaga Mitra di form pegawai.
  case TENAGA_MITRA = 'tenaga_mitra';
  case LAINNYA = 'lainnya';

  public function label(): string
  {
    return match ($this) {
      self::PIMPINAN => 'Pimpinan OPD',
      self::SEKRETARIS => 'Sekretaris OPD',
      self::KABID => 'Kepala Bidang / Bagian / Irban',
      self::KASI => 'Kepala Seksi / Sub Bagian / Sub Bidang',
      self::STAF => 'Staf / Pelaksana / Fungsional',
      self::TENAGA_MITRA => 'Tenaga Mitra',
      self::LAINNYA => 'Lainnya',
    };
  }

  public function description(): string
  {
    return match ($this) {
      self::PIMPINAN => 'Kepala Dinas/Badan, Inspektur, Camat, Lurah, Kepala Puskesmas, Direktur RSUD',
      self::SEKRETARIS => 'Sekretaris Dinas/Badan, Sekretaris Camat/Lurah',
      self::KABID => 'Pejabat eselon III di bawah Sekretaris',
      self::KASI => 'Pejabat eselon IV',
      self::STAF => 'Analis, pranata, bendahara, pengadministrasi, dll.',
      self::TENAGA_MITRA => 'Pengikut perjalanan dari luar OPD, tanpa tingkat jabatan',
      self::LAINNYA => 'Tidak yakin, role diatur Super Admin',
    };
  }

  /**
   * Role sistem default bagi pemangku jabatan pada tingkat ini.
   */
  public function role(): ?string
  {
    return match ($this) {
      self::PIMPINAN => 'kepala_opd',
      self::SEKRETARIS => 'sekretaris_opd',
      self::KABID => 'kabid_irban_kabag',
      self::KASI => 'kasubid_kasubag',
      self::STAF => 'staf',
      self::TENAGA_MITRA => User::TENAGA_MITRA_ROLE,
      self::LAINNYA => null,
    };
  }

  /**
   * Hanya Pimpinan & Sekretaris yang dibatasi satu pemangku per OPD. Kabid & Kasi
   * boleh banyak: tahapnya dicari lewat atasan langsung pemohon (users.atasan_id),
   * bukan dari pemangku tunggal jabatan, dan role-nya pun tidak dibatasi
   * (config role_uniqueness).
   */
  public function scope(): PositionScope
  {
    return match ($this) {
      self::PIMPINAN, self::SEKRETARIS => PositionScope::DEPARTMENT,
      default => PositionScope::NONE,
    };
  }

  /**
   * Tingkat yang bisa dipilih di form jabatan. Lainnya tidak ditawarkan; hanya
   * dipertahankan untuk jabatan khusus yang sudah ada (Walikota, Sekda, dst.).
   * Tenaga Mitra juga tidak ditawarkan: jabatannya hanya dibuat dari mode Tenaga Mitra.
   *
   * @return self[]
   */
  public static function selectable(): array
  {
    return array_values(array_filter(
      self::cases(),
      fn (self $tier) => ! in_array($tier, [self::LAINNYA, self::TENAGA_MITRA], true),
    ));
  }

  public static function fromRole(?string $role): self
  {
    // Role khusus unit (Camat, Seklur, dst.) bertingkat sama dengan role umumnya.
    $role = $role !== null ? DepartmentType::genericRole($role) : null;

    foreach (self::cases() as $tier) {
      if ($role !== null && $tier->role() === $role) {
        return $tier;
      }
    }

    return self::LAINNYA;
  }

  public static function guessFromName(string $name): self
  {
    return self::fromRole(Position::guessDefaultRole($name));
  }
}
