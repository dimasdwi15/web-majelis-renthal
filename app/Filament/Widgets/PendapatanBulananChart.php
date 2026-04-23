<?php

namespace App\Filament\Widgets;

use App\Enums\StatusTransaksi;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Filament\Support\RawJs;

class PendapatanBulananChart extends ChartWidget
{
    protected ?string $heading = '📊 Pendapatan Bulanan';
    protected ?string $description = 'Total pendapatan sewa per bulan (12 bulan terakhir)';

    protected static ?int $sort = 3; // ✅ HARUS STATIC

    protected int|string|array $columnSpan = 'full';
    protected ?string $maxHeight = '280px';

    public ?string $filter = '12';

    protected function getFilters(): ?array
    {
        return [
            '6'  => '6 Bulan Terakhir',
            '12' => '12 Bulan Terakhir',
        ];
    }

    protected function getData(): array
    {
        $bulan = (int) $this->filter;

        $statusBerhasil = [
            StatusTransaksi::Selesai->value,
            StatusTransaksi::Berjalan->value,
            StatusTransaksi::Terlambat->value,
            StatusTransaksi::Dikembalikan->value,
        ];

        // Ambil data pendapatan per bulan
        $data = collect(range($bulan - 1, 0))->map(function ($i) use ($statusBerhasil) {
            $tanggal = now()->subMonths($i);

            $pendapatan = (float) DB::table('transaksi')
                ->whereIn('status', $statusBerhasil)
                ->whereMonth('created_at', $tanggal->month)
                ->whereYear('created_at', $tanggal->year)
                ->sum('total_sewa');

            $denda = (float) DB::table('transaksi')
                ->whereIn('status', $statusBerhasil)
                ->whereMonth('created_at', $tanggal->month)
                ->whereYear('created_at', $tanggal->year)
                ->sum('total_denda');

            return [
                'label'      => $tanggal->translatedFormat('M Y'),
                'pendapatan' => $pendapatan,
                'denda'      => $denda,
            ];
        });

        return [
            'datasets' => [
                [
                    'label'           => 'Pendapatan Sewa',
                    'data'            => $data->pluck('pendapatan')->toArray(),
                    'backgroundColor' => 'rgba(77, 70, 46, 0.85)',
                    'borderColor'     => 'rgba(77, 70, 46, 1)',
                    'borderWidth'     => 2,
                    'borderRadius'    => 6,
                    'borderSkipped'   => false,
                ],
                [
                    'label'           => 'Denda',
                    'data'            => $data->pluck('denda')->toArray(),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.75)',
                    'borderColor'     => 'rgba(239, 68, 68, 1)',
                    'borderWidth'     => 2,
                    'borderRadius'    => 6,
                    'borderSkipped'   => false,
                ],
            ],
            'labels' => $data->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'top',
                ],
            'tooltip' => [
                'callbacks' => [
                    'label' => RawJs::make("
                        function(context) {
                            let value = context.raw || 0;
                            return context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(value);
                        }
                    "),
                    'footer' => RawJs::make("
                        function(tooltipItems) {
                            let total = 0;
                            tooltipItems.forEach(function(item) {
                                total += item.raw || 0;
                            });
                            return 'Total: Rp ' + new Intl.NumberFormat('id-ID').format(total);
                        }
                    "),
                ],
            ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(v){
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
                    }",
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
