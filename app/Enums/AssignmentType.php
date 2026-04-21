<?php

namespace App\Enums;

enum AssignmentType: string
{
    case SUBBAGIAN = 'subbagian';
    case BAGIAN = 'bagian';
    case ASISTEN = 'asisten';

    public function label(): string
    {
        return match ($this) {
            self::SUBBAGIAN => 'Sub Bagian',
            self::BAGIAN => 'Bagian',
            self::ASISTEN => 'Asisten',
        };
    }
}
