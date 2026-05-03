<?php

namespace App\Filament\Widgets;

use App\Models\Transaksi;
use Filament\Widgets\ChartWidget;

class GrafikPendapatanBulanan extends ChartWidget
{
    /**
     * Filament 5:
     *
     * $heading = NON STATIC
     * $sort    = STATIC
     *
     * Jadi:
     * heading -> protected ?string
     * sort    -> protected static ?int
     */

    protected ?string $heading = 'Grafik Pendapatan 12 Bulan Terakhir';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels = [];
        $dataSewa = [];
        $dataDenda = [];

        /**
         * Ambil data 12 bulan terakhir
         */
        for ($i = 11; $i >= 0; $i--) {
            $bulan = now()->copy()->subMonths($i);

            $labels[] = $bulan->translatedFormat('M Y');

            /**
             * Pendapatan sewa
             */
            $sewa = Transaksi::whereYear('created_at', $bulan->year)
                ->whereMonth('created_at', $bulan->month)
                ->whereIn('status', [
                    'selesai',
                    'berjalan',
                    'dikembalikan',
                ])
                ->sum('total_sewa');

            /**
             * Pendapatan denda
             */
            $denda = Transaksi::whereYear('created_at', $bulan->year)
                ->whereMonth('created_at', $bulan->month)
                ->where('status', 'selesai')
                ->sum('total_denda');

            $dataSewa[] = (float) $sewa;
            $dataDenda[] = (float) $denda;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan Sewa (Rp)',
                    'data' => $dataSewa,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59,130,246,0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Pendapatan Denda (Rp)',
                    'data' => $dataDenda,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245,158,11,0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],

            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
