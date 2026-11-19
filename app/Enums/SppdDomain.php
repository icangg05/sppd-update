<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum SppdDomain: string
{
  case DALAM_DAERAH = 'dalam_daerah';
  case LDDP         = 'lddp';
  case LDLP         = 'ldlp';
  case LUAR_NEGERI  = 'luar_negeri';

  public function label(): string
  {
    return match ($this) {
      self::DALAM_DAERAH => 'Dalam Daerah',
      self::LDDP         => 'Luar Daerah Dalam Provinsi (LDDP)',
      self::LDLP         => 'Luar Daerah Luar Provinsi (LDLP)',
      self::LUAR_NEGERI  => 'Luar Negeri',
    };
  }

  public function shortLabel(): string
  {
    return match ($this) {
      self::DALAM_DAERAH => 'Dalam Daerah',
      self::LDDP         => 'LDDP',
      self::LDLP         => 'LDLP',
      self::LUAR_NEGERI  => 'Luar Negeri',
    };
  }

  /**
   * Jabatan perjalanan Inspektorat (Penanggung Jawab s/d Admin Tim) hanya dipakai dalam daerah,
   * dan peserta SPT disusun menurut jabatan itu. Luar daerah (LDDP, LDLP, luar negeri) memakai
   * alur normal seperti OPD lain: pelaksana nomor 1, jabatan struktural yang tercetak.
   */
  public function usesTravelPositions(): bool
  {
    return $this === self::DALAM_DAERAH;
  }

  /**
   * Perjalanan dalam daerah tidak menghitung hari Minggu. Hari Sabtu ikut tidak dihitung bila
   * instansi pelaksana tidak menghitung Sabtu (Pengaturan Aplikasi, per tipe instansi; lihat
   * Department::countsSaturdayOnTrips).
   */
  public function skipsSundays(): bool
  {
    return $this === self::DALAM_DAERAH;
  }

  /**
   * Hari yang tidak dihitung dan tidak bisa jadi tanggal berangkat/kembali, sebagai nomor hari
   * Carbon/JavaScript (0 = Minggu, 6 = Sabtu). Domain selain dalam daerah menghitung semua hari.
   *
   * @return list<int>
   */
  public function offDays(bool $countsSaturday = true): array
  {
    if (! $this->skipsSundays()) {
      return [];
    }

    return $countsSaturday ? [CarbonInterface::SUNDAY] : [CarbonInterface::SATURDAY, CarbonInterface::SUNDAY];
  }

  public function isOffDay(CarbonInterface $date, bool $countsSaturday = true): bool
  {
    return in_array($date->dayOfWeek, $this->offDays($countsSaturday), true);
  }

  /**
   * Lama perjalanan dalam hari, termasuk hari berangkat dan kembali. Dalam daerah tidak menghitung
   * hari libur (offDays): Rabu s/d Selasa berikutnya = 6 hari bila Sabtu dihitung, 5 hari bila tidak.
   */
  public function travelDays(CarbonInterface $start, CarbonInterface $end, bool $countsSaturday = true): int
  {
    $start = $start->copy()->startOfDay();
    $end = $end->copy()->startOfDay();
    if ($end->lt($start)) {
      return 0;
    }

    $offDays = $this->offDays($countsSaturday);
    if ($offDays === []) {
      return (int) $start->diffInDays($end) + 1;
    }

    $days = 0;
    for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
      $days += in_array($day->dayOfWeek, $offDays, true) ? 0 : 1;
    }

    return $days;
  }

  /** Daftar nilai enum, untuk aturan validasi `in:`. */
  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }
}
