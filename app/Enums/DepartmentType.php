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
  case RUMAH_SAKIT = 'rumah_sakit';
  case BAGIAN      = 'bagian';
  case SUBBAGIAN   = 'subbagian';
  case ASISTEN     = 'asisten';

  /**
   * Entitas yang mengelola datanya sendiri (anggaran/SPPD) dan terpisah dari
   * zona data induknya — mis. Kelurahan di bawah Kecamatan, Puskesmas di bawah
   * Dinas Kesehatan. Rumah Sakit ikut mandiri agar datanya tetap terpisah bila kelak
   * diletakkan di bawah Dinas Kesehatan. Lihat Department::getScopedDescendantIds/getScopeRootDepartment.
   */
  public function isIndependentZone(): bool
  {
    return match ($this) {
      self::KELURAHAN, self::PUSKESMAS, self::RUMAH_SAKIT => true,
      default => false,
    };
  }

  /**
   * Centang "Hitung hari Sabtu" sudah tercentang saat membuat SPPD dalam daerah untuk pegawai
   * unit bertipe ini (Puskesmas & Rumah Sakit bekerja enam hari). Hanya nilai awal: pembuat SPPD
   * tetap bebas mengubahnya per SPPD (keputusan user 2026-11-19).
   */
  public function checksSaturdayByDefault(): bool
  {
    return match ($this) {
      self::PUSKESMAS, self::RUMAH_SAKIT => true,
      default => false,
    };
  }

  /**
   * Sebutan pejabat di tipe instansi ini bila berbeda dari label role-nya: Rumah Sakit
   * memakai role Kepala OPD & Sekretaris OPD, tetapi pejabatnya disebut Direktur &
   * Sekretaris. Null = pakai label role.
   */
  public function roleTitle(string $role): ?string
  {
    return match ($this) {
      self::RUMAH_SAKIT => ['kepala_opd' => 'Direktur', 'sekretaris_opd' => 'Sekretaris'][$role] ?? null,
      default => null,
    };
  }

  /**
   * Role khusus pemangku tingkat Pimpinan/Sekretaris di tipe unit ini (keputusan user 2026-11-20):
   * Kecamatan Camat/Sekcam, Kelurahan Lurah/Seklur, Puskesmas Kepala Puskesmas. Tipe lain (termasuk
   * Rumah Sakit, yang cukup diberi sebutan lewat roleTitle) memakai role apa adanya. Alur persetujuan
   * mengenali role khusus lewat SppdWorkflowService::getRoleSynonyms.
   */
  public function specificRole(string $role): string
  {
    return self::SPECIFIC_ROLES[$this->value][$role] ?? $role;
  }

  /** @var array<string, array<string, string>> tipe unit => [role umum => role khusus] */
  private const SPECIFIC_ROLES = [
    'kecamatan' => ['kepala_opd' => 'camat', 'sekretaris_opd' => 'sekcam'],
    'kelurahan' => ['kepala_opd' => 'lurah', 'sekretaris_opd' => 'seklur'],
    'puskesmas' => ['kepala_opd' => 'kapus'],
  ];

  /** Role umum yang di tipe unit ini diganti role khusus (lihat specificRole). */
  public function replacedGenericRoles(): array
  {
    return array_keys(self::SPECIFIC_ROLES[$this->value] ?? []);
  }

  /** Semua role khusus tipe unit: camat, sekcam, lurah, seklur, kapus. */
  public static function specificRoles(): array
  {
    return array_values(array_merge(...array_map('array_values', array_values(self::SPECIFIC_ROLES))));
  }

  /** Role umum di balik role khusus (camat → kepala_opd, seklur → sekretaris_opd); role lain apa adanya. */
  public static function genericRole(string $role): string
  {
    foreach (self::SPECIFIC_ROLES as $pairs) {
      if (($generic = array_search($role, $pairs, true)) !== false) {
        return $generic;
      }
    }

    return $role;
  }

  /** Tipe unit pemilik role khusus (camat → Kecamatan, kapus → Puskesmas); null untuk role lain. */
  public static function forSpecificRole(?string $role): ?self
  {
    foreach (self::SPECIFIC_ROLES as $type => $pairs) {
      if (in_array($role, $pairs, true)) {
        return self::from($type);
      }
    }

    return null;
  }

  /** Role khusus suatu role umum, urut tipe unit: kepala_opd → [camat, lurah, kapus]. */
  public static function specificRolesFor(string $genericRole): array
  {
    return array_values(array_filter(array_map(fn (array $pairs) => $pairs[$genericRole] ?? null, self::SPECIFIC_ROLES)));
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
      self::RUMAH_SAKIT => 'Rumah Sakit',
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
      self::RUMAH_SAKIT => 'bg-orange-50 text-orange-700 border-orange-200',
    };
  }
}
