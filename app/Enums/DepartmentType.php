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
}
