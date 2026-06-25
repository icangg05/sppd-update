<?php

namespace App\Enums;

enum PositionRequestStatus: string
{
  case PENDING = 'pending';
  case APPROVED = 'approved';
  case REJECTED = 'rejected';

  public function label(): string
  {
    return match ($this) {
      self::PENDING => 'Menunggu',
      self::APPROVED => 'Disetujui',
      self::REJECTED => 'Ditolak',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::PENDING => 'yellow',
      self::APPROVED => 'green',
      self::REJECTED => 'red',
    };
  }
}
