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

  /** Perjalanan dalam daerah hanya dihitung pada hari kerja: Sabtu & Minggu dilewati. */
  public function skipsWeekends(): bool
  {
    return $this === self::DALAM_DAERAH;
  }

  /**
   * Lama perjalanan dalam hari, termasuk hari berangkat dan kembali. Dalam daerah hanya
   * menghitung Senin–Jumat: Rabu s/d Selasa berikutnya = 5 hari.
   */
  public function travelDays(CarbonInterface $start, CarbonInterface $end): int
  {
    $start = $start->copy()->startOfDay();
    $end = $end->copy()->startOfDay();
    if ($end->lt($start)) {
      return 0;
    }

    if (! $this->skipsWeekends()) {
      return (int) $start->diffInDays($end) + 1;
    }

    $days = 0;
    for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
      $days += $day->isWeekend() ? 0 : 1;
    }

    return $days;
  }

  /** Daftar nilai enum, untuk aturan validasi `in:`. */
  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }
}
