<?php

namespace App\Enums;

enum EmployeeType: string
{
  case PNS      = 'pns';
  case PPPK     = 'pppk';
  case HONORER  = 'honorer';
  case DPRD     = 'dprd';
  case LAINNYA  = 'lainnya';

  public function label(): string
  {
    return match ($this) {
      self::PNS      => 'PNS',
      self::PPPK     => 'PPPK',
      self::HONORER  => 'Honorer',
      self::DPRD     => 'Anggota DPRD',
      self::LAINNYA  => 'Lainnya',
    };
  }
}
