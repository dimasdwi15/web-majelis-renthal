<?php

namespace App\Filament\Widgets;

use App\Enums\StatusTransaksi;
use App\Models\Transaksi;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TransaksiOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Polling setiap 5 detik untuk notifikasi real-time
    protected ?string $pollingInterval = '5s';
    
    protected function getStats(): array
    {
        return [
            Stat::make(
                'Menunggu Pembayaran',
                Transaksi::where('status', StatusTransaksi::MenungguPembayaran)->count()
            )
                ->description('Transaksi baru perlu diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url('/admin/transaksis?tableFilters[status][value]=menunggu_pembayaran'),

            Stat::make(
                'Sedang Berjalan',
                Transaksi::whereIn('status', [
                    StatusTransaksi::Berjalan,
                    StatusTransaksi::Dibayar,
                ])->count()
            )
                ->description('Barang sedang disewa')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),

            Stat::make(
                'Terlambat',
                Transaksi::where('status', StatusTransaksi::Terlambat)->count()
            )
                ->description('Perlu ditindaklanjuti')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->url('/admin/transaksis?tableFilters[status][value]=terlambat'),

            Stat::make(
                'Dikembalikan',
                Transaksi::where('status', StatusTransaksi::Dikembalikan)->count()
            )
                ->description('Menunggu penyelesaian denda')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('warning')
                ->url('/admin/transaksis?tableFilters[status][value]=dikembalikan'),
        ];
    }
}
