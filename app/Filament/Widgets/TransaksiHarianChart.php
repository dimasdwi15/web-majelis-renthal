<?php

namespace App\Filament\Widgets;

use App\Enums\StatusTransaksi;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TransaksiHarianChart extends ChartWidget
{
    protected ?string $heading = '📈 Tren Transaksi Harian';
    protected ?string $description = 'Jumlah transaksi masuk vs selesai per hari';

    protected static ?int $sort = 3; // ✅ tetap static

    protected int|string|array $columnSpan = 2;
    protected ?string $maxHeight = '260px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7'  => '7 Hari',
            '14' => '14 Hari',
            '30' => '30 Hari',
        ];
    }

    protected function getData(): array
    {
        $hari = (int) $this->filter;

        $data = collect(range($hari - 1, 0))->map(function ($i) {
            $tanggal = now()->subDays($i)->toDateString();

            return [
                'label'   => now()->subDays($i)->translatedFormat('d M'),
                'masuk'   => (int) DB::table('transaksi')
                    ->whereDate('created_at', $tanggal)
                    ->count(),
                'selesai' => (int) DB::table('transaksi')
                    ->where('status', StatusTransaksi::Selesai->value)
                    ->whereDate('updated_at', $tanggal)
                    ->count(),
                'terlambat' => (int) DB::table('transaksi')
                    ->where('status', StatusTransaksi::Terlambat->value)
                    ->whereDate('updated_at', $tanggal)
                    ->count(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label'           => 'Transaksi Masuk',
                    'data'            => $data->pluck('masuk')->toArray(),
                    'borderColor'     => 'rgba(59, 130, 246, 1)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.08)',
                    'borderWidth'     => 2,
                    'pointRadius'     => 3,
                    'pointHoverRadius' => 6,
                    'tension'         => 0.4,
                    'fill'            => true,
                ],
                [
                    'label'           => 'Transaksi Selesai',
                    'data'            => $data->pluck('selesai')->toArray(),
                    'borderColor'     => 'rgba(34, 197, 94, 1)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.08)',
                    'borderWidth'     => 2,
                    'pointRadius'     => 3,
                    'pointHoverRadius' => 6,
                    'tension'         => 0.4,
                    'fill'            => true,
                ],
                [
                    'label'           => 'Terlambat',
                    'data'            => $data->pluck('terlambat')->toArray(),
                    'borderColor'     => 'rgba(239, 68, 68, 1)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.08)',
                    'borderWidth'     => 2,
                    'pointRadius'     => 3,
                    'pointHoverRadius' => 6,
                    'tension'         => 0.4,
                    'fill'            => false,
                    'borderDash'      => [5, 4],
                ],
            ],
            'labels' => $data->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => [
                        'stepSize' => 1,
                    ],
                    'grid' => [
                        'color' => 'rgba(0,0,0,0.05)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
