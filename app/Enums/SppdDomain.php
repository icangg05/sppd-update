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
   * Perjalanan dalam daerah: hari Sabtu dan Minggu dihitung atau tidak dipilih per SPPD oleh
   * pembuatnya (centang di form, disimpan di counts_saturday/counts_sunday). Domain lain selalu
   * menghitung semua hari.
   */
  public function hasWeekendChoice(): bool
  {
    return $this === self::DALAM_DAERAH;
  }

  /**
   * Hari yang tidak dihitung dan tidak bisa jadi tanggal berangkat/kembali, sebagai nomor hari
   * Carbon/JavaScript (0 = Minggu, 6 = Sabtu). Domain selain dalam daerah menghitung semua hari.
   *
   * @return list<int>
   */
  public function offDays(bool $countsSaturday = true, bool $countsSunday = false): array
  {
    if (! $this->hasWeekendChoice()) {
      return [];
    }

    return array_values(array_filter([
      $countsSaturday ? null : CarbonInterface::SATURDAY,
      $countsSunday ? null : CarbonInterface::SUNDAY,
    ], fn ($day) => $day !== null));
  }

  public function isOffDay(CarbonInterface $date, bool $countsSaturday = true, bool $countsSunday = false): bool
  {
    return in_array($date->dayOfWeek, $this->offDays($countsSaturday, $countsSunday), true);
  }

  /**
   * Lama perjalanan dalam hari, termasuk hari berangkat dan kembali. Dalam daerah tidak menghitung
   * hari yang tidak dicentang (offDays): Rabu s/d Selasa berikutnya = 7 hari bila Sabtu & Minggu
   * dihitung, 6 bila hanya Sabtu, 5 bila keduanya tidak.
   */
  public function travelDays(CarbonInterface $start, CarbonInterface $end, bool $countsSaturday = true, bool $countsSunday = false): int
  {
    $start = $start->copy()->startOfDay();
    $end = $end->copy()->startOfDay();
    if ($end->lt($start)) {
      return 0;
    }

    $offDays = $this->offDays($countsSaturday, $countsSunday);
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
