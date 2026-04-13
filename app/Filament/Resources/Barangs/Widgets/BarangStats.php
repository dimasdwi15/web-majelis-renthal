<?php

namespace App\Filament\Resources\Barangs\Widgets;

use App\Models\Barang;
use App\Models\Transaksi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BarangStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make('Total Barang', Barang::count())
                ->description('Jumlah semua barang')
                ->color('primary'),

            Stat::make('Stok Tersedia', Barang::sum('stok'))
                ->description('Total stok tersedia')
                ->color('success'),

            Stat::make('Barang Disewa',
                Transaksi::where('status', 'berjalan')->count()
            )
                ->description('Sedang disewa')
                ->color('warning'),

            Stat::make('Maintenance',
                Barang::where('status', 'nonaktif')->count()
            )
                ->description('Perlu perbaikan')
                ->color('danger'),
        ];
    }
}
