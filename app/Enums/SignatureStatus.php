<?php

namespace App\Enums;

enum SignatureStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SIGNED = 'signed';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Tanda Tangan',
            self::PROCESSING => 'Sedang Diproses',
            self::SIGNED => 'Sudah Ditandatangani',
            self::REJECTED => 'Ditolak',
        };
    }
}
