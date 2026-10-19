<?php

namespace App\Enums;

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

  /** Daftar nilai enum, untuk aturan validasi `in:`. */
  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }
}
