<?php

namespace App\Enums;

enum DprdPartai: string
{
  case PDIP     = 'F. PDI-P';
  case GOLKAR   = 'F. GOLKAR';
  case GERINDRA = 'F. GERINDRA';
  case NASDEM   = 'F. NASDEM';
  case PKB      = 'F. PKB';
  case PKS      = 'F. PKS';
  case PAN      = 'F. PAN';
  case DEMOKRAT = 'F. DEMOKRAT';
  case PPP      = 'F. PPP';
  case PSI      = 'F. PSI';
  case PERINDO  = 'F. PERINDO';
  case HANURA   = 'F. HANURA';

  public function label(): string
  {
    return $this->value;
  }
}
