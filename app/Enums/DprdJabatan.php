<?php

namespace App\Enums;

enum DprdJabatan: string
{
  case KETUA    = 'Ketua DPRD';
  case WAKIL_1  = 'Wakil Ketua I';
  case WAKIL_2  = 'Wakil Ketua II';
  case WAKIL_3  = 'Wakil Ketua III';
  case KOMISI_1 = 'Anggota Komisi I';
  case KOMISI_2 = 'Anggota Komisi II';
  case KOMISI_3 = 'Anggota Komisi III';
  case KOMISI_4 = 'Anggota Komisi IV';

  public function label(): string
  {
    return $this->value;
  }
}
