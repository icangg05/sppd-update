<?php

namespace App\Enums;

enum SppdStatus: string
{
  case DRAFT       = 'draft';
  case IN_PROGRESS = 'in_progress';
  case APPROVED    = 'approved';
  case REJECTED    = 'rejected';
  case COMPLETED   = 'completed';

  public function label(): string
  {
    return match ($this) {
      self::DRAFT       => 'Draft',
      self::IN_PROGRESS => 'Dalam Proses',
      self::APPROVED    => 'Disetujui',
      self::REJECTED    => 'Ditolak',
      self::COMPLETED   => 'Selesai',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::DRAFT       => 'gray',
      self::IN_PROGRESS => 'blue',
      self::APPROVED    => 'green',
      self::REJECTED    => 'red',
      self::COMPLETED   => 'emerald',
    };
  }
}
