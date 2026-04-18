<?php

namespace App\Enums;

enum StatusTransaksi: string
{
    case MenungguPembayaran = 'menunggu_pembayaran';
    case Dibayar             = 'dibayar';
    case Berjalan            = 'berjalan';
    case Terlambat           = 'terlambat';
    case Dikembalikan        = 'dikembalikan';
    case Selesai             = 'selesai';
    case Dibatalkan          = 'dibatalkan';

    /**
     * Label bahasa Indonesia untuk UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'Menunggu Pembayaran',
            self::Dibayar            => 'Dibayar',
            self::Berjalan           => 'Berjalan',
            self::Terlambat          => 'Terlambat',
            self::Dikembalikan       => 'Dikembalikan',
            self::Selesai            => 'Selesai',
            self::Dibatalkan         => 'Dibatalkan',
        };
    }

    /**
     * Warna untuk Filament badge & UI.
     */
    public function color(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'warning',
            self::Dibayar            => 'info',
            self::Berjalan           => 'primary',
            self::Terlambat          => 'danger',
            self::Dikembalikan       => 'warning',
            self::Selesai            => 'success',
            self::Dibatalkan         => 'gray',
        };
    }

    /**
     * Hex color untuk Blade views.
     */
    public function hex(): string
    {
        return match ($this) {
            self::MenungguPembayaran => '#f59e0b',
            self::Dibayar            => '#3b82f6',
            self::Berjalan           => '#10b981',
            self::Terlambat          => '#ef4444',
            self::Dikembalikan       => '#f97316',
            self::Selesai            => '#22c55e',
            self::Dibatalkan         => '#6b7280',
        };
    }

    /**
     * CSS class untuk background badge di Blade.
     */
    public function bgClass(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'bg-yellow-500',
            self::Dibayar            => 'bg-blue-500',
            self::Berjalan           => 'bg-green-500',
            self::Terlambat          => 'bg-red-500',
            self::Dikembalikan       => 'bg-orange-500',
            self::Selesai            => 'bg-emerald-500',
            self::Dibatalkan         => 'bg-gray-500',
        };
    }

    /**
     * Icon Material Symbols.
     */
    public function icon(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'hourglass_top',
            self::Dibayar            => 'paid',
            self::Berjalan           => 'local_shipping',
            self::Terlambat          => 'warning',
            self::Dikembalikan       => 'assignment_return',
            self::Selesai            => 'check_circle',
            self::Dibatalkan         => 'cancel',
        };
    }

    /**
     * Status-status yang dianggap aktif (belum selesai).
     */
    public static function aktif(): array
    {
        return [
            self::MenungguPembayaran,
            self::Dibayar,
            self::Berjalan,
            self::Terlambat,
            self::Dikembalikan,
        ];
    }
}
