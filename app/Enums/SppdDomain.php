<?php

namespace App\Enums;

enum SppdDomain: string
{
    case DALAM_DAERAH = 'dalam_daerah';
    case LDDP = 'lddp';
    case LDLP = 'ldlp';

    public function label(): string
    {
        return match ($this) {
            self::DALAM_DAERAH => 'Dalam Daerah',
            self::LDDP => 'Luar Daerah Dalam Provinsi (LDDP)',
            self::LDLP => 'Luar Daerah Luar Provinsi (LDLP)',
        };
    }
}
