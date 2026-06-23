<?php

namespace App\Enums;

enum PositionScope: string
{
  // Boleh dijabat lebih dari satu orang (mis. Staf, Fungsional Umum).
  case NONE = 'none';

  // Hanya boleh satu pemangku dalam satu OPD / unit kerja (mis. Kepala Dinas, Camat).
  case DEPARTMENT = 'department';

  // Hanya boleh satu pemangku di seluruh sistem (mis. Walikota, Sekretaris Daerah).
  case SYSTEM = 'system';

  public function label(): string
  {
    return match ($this) {
      self::NONE => 'Tidak dibatasi',
      self::DEPARTMENT => 'Satu per OPD / unit kerja',
      self::SYSTEM => 'Satu untuk seluruh sistem',
    };
  }
}
