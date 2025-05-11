<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Pembayaran',
            self::SUCCESS => 'Berhasil',
            self::FAILED => 'Gagal',
        };
    }
}
