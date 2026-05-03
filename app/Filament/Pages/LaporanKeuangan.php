<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\GrafikMetodePembayaran;
use App\Filament\Widgets\GrafikPendapatanBulanan;
use App\Filament\Widgets\StatsLaporanKeuangan;
use App\Filament\Widgets\TabelLaporanTransaksi;
use App\Models\Transaksi;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Spatie\SimpleExcel\SimpleExcelWriter;

class LaporanKeuangan extends Page
{
    /**
     * Navigation
     */
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Keuangan';

    protected static ?int $navigationSort = 1;

    /**
     * Filament 5:
     * $view = NON STATIC
     */
    protected string $view = 'filament.pages.laporan-keuangan';

    /**
     * Filter bulan & tahun
     */
    public string $bulan;

    public string $tahun;

    /**
     * Navigation Group
     */
    public static function getNavigationGroup(): ?string
    {
        return 'Laporan';
    }

    public function mount(): void
    {
        $this->bulan = now()->format('m');
        $this->tahun = now()->format('Y');
    }

    protected function getFooterWidgets(): array
    {
        return [
            TabelLaporanTransaksi::class,
            GrafikPendapatanBulanan::class,
        ];
    }

    /**
     * Header Actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(route('export.laporan.keuangan'))
                ->openUrlInNewTab(),
        ];
    }
}
