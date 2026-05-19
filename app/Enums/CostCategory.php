<?php

namespace App\Enums;

enum CostCategory: string
{
  case TRANSPORTASI      = 'transportasi';
  case AKOMODASI         = 'akomodasi';
  case UANG_HARIAN       = 'uang_harian';
  case UANG_REPRESENTASI = 'uang_representasi';
  case LAINNYA           = 'lainnya';

  public function label(): string
  {
    return match ($this) {
      self::TRANSPORTASI      => 'Transportasi',
      self::AKOMODASI         => 'Akomodasi',
      self::UANG_HARIAN       => 'Uang Harian',
      self::UANG_REPRESENTASI => 'Uang Representasi',
      self::LAINNYA           => 'Biaya Lainnya',
    };
  }
}
