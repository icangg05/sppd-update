<?php

namespace App\Enums;

use App\Models\Position;

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
  case LAINNYA = 'lainnya';

  public function label(): string
  {
    return match ($this) {
      self::PIMPINAN => 'Pimpinan OPD',
      self::SEKRETARIS => 'Sekretaris OPD',
      self::KABID => 'Kepala Bidang / Bagian / Irban',
      self::KASI => 'Kepala Seksi / Sub Bagian / Sub Bidang',
      self::STAF => 'Staf / Pelaksana / Fungsional',
      self::LAINNYA => 'Lainnya',
    };
  }

  public function description(): string
  {
    return match ($this) {
      self::PIMPINAN => 'Kepala Dinas/Badan, Inspektur, Camat, Lurah, Kepala Puskesmas',
      self::SEKRETARIS => 'Sekretaris Dinas/Badan, Sekretaris Camat/Lurah',
      self::KABID => 'Pejabat eselon III di bawah Sekretaris',
      self::KASI => 'Pejabat eselon IV',
      self::STAF => 'Analis, pranata, bendahara, pengadministrasi, dll.',
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
      self::LAINNYA => null,
    };
  }

  /**
   * Jabatan struktural hanya boleh satu pemangku per OPD; staf boleh banyak.
   */
  public function scope(): PositionScope
  {
    return match ($this) {
      self::STAF, self::LAINNYA => PositionScope::NONE,
      default => PositionScope::DEPARTMENT,
    };
  }

  /**
   * Tingkat yang bisa dipilih di form jabatan. Lainnya tidak ditawarkan; hanya
   * dipertahankan untuk jabatan khusus yang sudah ada (Walikota, Sekda, dst.).
   *
   * @return self[]
   */
  public static function selectable(): array
  {
    return array_values(array_filter(self::cases(), fn (self $tier) => $tier !== self::LAINNYA));
  }

  public static function fromRole(?string $role): self
  {
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
