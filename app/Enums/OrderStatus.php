<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';
    case CANCELLED = 'CANCELLED';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Pembayaran',
            self::PAID => 'Sudah Dibayar',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}