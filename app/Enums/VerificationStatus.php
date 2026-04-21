<?php

namespace App\Enums;

enum VerificationStatus: string
{
  case PENDING = 'pending';
  case VERIFIED = 'verified';
  case RETURNED = 'returned';

  public function label(): string
  {
    return match ($this) {
      self::PENDING => 'Menunggu Verifikasi',
      self::VERIFIED => 'Terverifikasi',
      self::RETURNED => 'Dikembalikan',
    };
  }
}
