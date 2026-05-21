<?php

namespace App\Enums;

enum SignatureProvider: string
{
    case LOCAL_PROXY = 'local_proxy';
    case BSSN = 'bssn';

    public function label(): string
    {
        return match ($this) {
            self::LOCAL_PROXY => 'Local Proxy',
            self::BSSN => 'BSSN e-Sign',
        };
    }
}
