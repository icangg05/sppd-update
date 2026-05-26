<?php

namespace App\Enums;

enum SignatureDocumentType: string
{
  case SPPD     = 'sppd';
  case SPT      = 'spt';
  case KUITANSI = 'kuitansi';

  public function label(): string
  {
    return match ($this) {
      self::SPPD     => 'SPPD',
      self::SPT      => 'SPT',
      self::KUITANSI => 'Kuitansi',
    };
  }
}
