<?php

namespace App\Enums;

enum SppdDomain: string
{
    case DALAM_DAERAH = 'dalam_daerah';
    case LUAR_DAERAH = 'luar_daerah';
    case BIMTEK = 'bimtek';

    public function label(): string
    {
        return match ($this) {
            self::DALAM_DAERAH => 'Dalam Daerah',
            self::LUAR_DAERAH => 'Luar Daerah',
            self::BIMTEK => 'Bimtek',
        };
    }
}
