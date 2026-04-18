<?php

namespace App\Enums;

enum MetodePembayaran: string
{
    case Midtrans = 'midtrans';
    case Tunai    = 'tunai';

    public function label(): string
    {
        return match ($this) {
            self::Midtrans => 'Cashless (Midtrans)',
            self::Tunai    => 'Tunai (COD)',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Midtrans => 'credit_card',
            self::Tunai    => 'payments',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Midtrans => 'info',
            self::Tunai    => 'success',
        };
    }

    public function isCod(): bool
    {
        return $this === self::Tunai;
    }

    public function isCashless(): bool
    {
        return $this === self::Midtrans;
    }
}
