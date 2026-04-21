<?php

namespace App\Enums;

enum EmployeeType: string
{
  case PNS      = 'pns';
  case PPPK     = 'pppk';
  case HONORER  = 'honorer';
  case PIMPINAN = 'pimpinan';
  case DPRD     = 'dprd';

  public function label(): string
  {
    return match ($this) {
      self::PNS      => 'PNS',
      self::PPPK     => 'PPPK',
      self::HONORER  => 'Honorer',
      self::PIMPINAN => 'Pimpinan',
      self::DPRD     => 'Anggota DPRD',
    };
  }
}
