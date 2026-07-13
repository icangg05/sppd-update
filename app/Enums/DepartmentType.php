<?php

namespace App\Enums;

enum DepartmentType: string
{
  case OPD         = 'opd';
  case DINKES      = 'dinkes';
  case DPRD        = 'dprd';
  case SETDA       = 'setda';
  case SEKRETARIAT = 'sekretariat';
  case KECAMATAN   = 'kecamatan';
  case KELURAHAN   = 'kelurahan';
  case PUSKESMAS   = 'puskesmas';
  case BAGIAN      = 'bagian';
  case SUBBAGIAN   = 'subbagian';
  case ASISTEN     = 'asisten';

  /**
   * Entitas yang mengelola datanya sendiri (anggaran/SPPD) dan terpisah dari
   * zona data induknya — mis. Kelurahan di bawah Kecamatan, Puskesmas di bawah
   * Dinas Kesehatan. Lihat Department::getScopedDescendantIds/getScopeRootDepartment.
   */
  public function isIndependentZone(): bool
  {
    return match ($this) {
      self::KELURAHAN, self::PUSKESMAS => true,
      default => false,
    };
  }

  public function label(): string
  {
    return match ($this) {
      self::OPD         => 'OPD',
      self::DINKES      => 'Dinas Kesehatan',
      self::DPRD        => 'DPRD',
      self::SETDA       => 'Sekretariat Daerah',
      self::SEKRETARIAT => 'Sekretariat',
      self::KECAMATAN   => 'Kecamatan',
      self::KELURAHAN   => 'Kelurahan',
      self::PUSKESMAS   => 'Puskesmas',
      self::BAGIAN      => 'Bagian',
      self::SUBBAGIAN   => 'Sub Bagian',
      self::ASISTEN     => 'Asisten',
    };
  }

  /** Kelas Tailwind badge per tipe (bg/teks/border). */
  public function badgeClasses(): string
  {
    return match ($this) {
      self::OPD         => 'bg-rose-50 text-rose-700 border-rose-200',
      self::DINKES      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
      self::DPRD        => 'bg-purple-50 text-purple-700 border-purple-200',
      // Struktur Sekretariat Daerah: deret warna bertetangga (analog) — menyatu tapi tiap tingkat beda
      self::SETDA       => 'bg-violet-50 text-violet-700 border-violet-200',
      self::ASISTEN     => 'bg-indigo-50 text-indigo-700 border-indigo-200',
      self::BAGIAN      => 'bg-sky-50 text-sky-700 border-sky-200',
      self::SUBBAGIAN   => 'bg-teal-50 text-teal-700 border-teal-200',
      self::SEKRETARIAT => 'bg-cyan-50 text-cyan-700 border-cyan-200',
      self::KECAMATAN   => 'bg-amber-50 text-amber-700 border-amber-200',
      self::KELURAHAN   => 'bg-lime-50 text-lime-700 border-lime-200',
      self::PUSKESMAS   => 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
    };
  }
}
