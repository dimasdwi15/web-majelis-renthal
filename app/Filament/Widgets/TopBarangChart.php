<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopBarangChart extends ChartWidget
{
    protected ?string $heading = '🏆 Barang Paling Sering Disewa';
    protected ?string $description = 'Peringkat barang berdasarkan total unit yang disewa';

    protected static ?int $sort = 4; 

    protected int|string|array $columnSpan = 'full';
    protected ?string $maxHeight = '260px';

    public ?string $filter = 'semua';

    protected function getFilters(): ?array
    {
        return [
            'semua'  => 'Semua Waktu',
            'bulan'  => 'Bulan Ini',
            'minggu' => 'Minggu Ini',
        ];
    }

    protected function getData(): array
    {
        $query = DB::table('transaksi_detail as td')
            ->join('barang as b', 'b.id', '=', 'td.barang_id')
            ->join('transaksi as t', 't.id', '=', 'td.transaksi_id')
            ->whereNotIn('t.status', ['dibatalkan'])
            ->select(
                'b.nama',
                DB::raw('SUM(td.jumlah) as total_unit'),
                DB::raw('SUM(td.subtotal) as total_pendapatan'),
                DB::raw('COUNT(DISTINCT td.transaksi_id) as total_transaksi')
            )
            ->groupBy('b.id', 'b.nama')
            ->orderByDesc('total_unit')
            ->limit(7);

        if ($this->filter === 'bulan') {
            $query->whereMonth('t.created_at', now()->month)
                  ->whereYear('t.created_at', now()->year);
        } elseif ($this->filter === 'minggu') {
            $query->whereBetween('t.created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        }

        $hasil = $query->get();

        // Warna gradient untuk tiap bar
        $warna = [
            'rgba(77, 70, 46, 0.9)',
            'rgba(59, 130, 246, 0.85)',
            'rgba(34, 197, 94, 0.85)',
            'rgba(245, 158, 11, 0.85)',
            'rgba(168, 85, 247, 0.85)',
            'rgba(239, 68, 68, 0.85)',
            'rgba(20, 184, 166, 0.85)',
        ];

        return [
            'datasets' => [
                [
                    'label'           => 'Total Unit Disewa',
                    'data'            => $hasil->pluck('total_unit')->toArray(),
                    'backgroundColor' => array_slice($warna, 0, $hasil->count()),
                    'borderRadius'    => 6,
                    'borderSkipped'   => false,
                    'borderWidth'     => 0,
                ],
            ],
            'labels' => $hasil->pluck('nama')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',   // horizontal bar chart
            'plugins'   => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [],
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks'       => [
                        'stepSize' => 1,
                    ],
                    'grid' => [
                        'color' => 'rgba(0,0,0,0.05)',
                    ],
                ],
                'y' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
