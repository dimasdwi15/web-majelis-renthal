<?php

namespace App\Filament\Resources\Transaksis\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Transaksi;
use App\Models\Denda;
use App\Enums\StatusTransaksi;

class TransaksiStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // ── ① TOTAL TRANSAKSI ───────────────────────────────────────────
        $total        = Transaksi::count();
        $hariIni      = Transaksi::whereDate('created_at', today())->count();
        $kemarin      = Transaksi::whereDate('created_at', today()->subDay())->count();
        $bulanIni     = Transaksi::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $selisihHarian = $hariIni - $kemarin;

        $trendTotal = collect(range(6, 0))
            ->map(fn($i) => Transaksi::whereDate('created_at', now()->subDays($i))->count())
            ->toArray();

        // ── ② MENUNGGU PEMBAYARAN ───────────────────────────────────────
        $menunggu       = Transaksi::where('status', StatusTransaksi::MenungguPembayaran)->count();
        $nilaiMenunggu  = Transaksi::where('status', StatusTransaksi::MenungguPembayaran)->sum('total_sewa');
        $midtrans       = Transaksi::where('status', StatusTransaksi::MenungguPembayaran)
            ->where('metode_pembayaran', 'midtrans')->count();
        $tunai          = Transaksi::where('status', StatusTransaksi::MenungguPembayaran)
            ->where('metode_pembayaran', 'tunai')->count();

        // Transaksi menunggu yang sudah lewat tanggal ambil (urgent)
        $terlambatBayar = Transaksi::where('status', StatusTransaksi::MenungguPembayaran)
            ->whereDate('tanggal_ambil', '<', today())
            ->count();

        $trendMenunggu = collect(range(6, 0))
            ->map(fn($i) => Transaksi::where('status', StatusTransaksi::MenungguPembayaran)
                ->whereDate('created_at', now()->subDays($i))->count())
            ->toArray();

        // ── ③ TRANSAKSI AKTIF / BERJALAN ───────────────────────────────
        $aktif          = Transaksi::whereIn('status', [
            StatusTransaksi::Berjalan,
            StatusTransaksi::Dibayar,
        ])->count();
        $berjalan       = Transaksi::where('status', StatusTransaksi::Berjalan)->count();
        $siapAmbil      = Transaksi::where('status', StatusTransaksi::Dibayar)->count();
        $jatuhTempoHariIni = Transaksi::whereIn('status', [
            StatusTransaksi::Berjalan,
            StatusTransaksi::Dibayar,
        ])->whereDate('tanggal_kembali', today())->count();
        $jatuhTempoBesok   = Transaksi::whereIn('status', [
            StatusTransaksi::Berjalan,
            StatusTransaksi::Dibayar,
        ])->whereDate('tanggal_kembali', today()->addDay())->count();

        $trendAktif = collect(range(6, 0))
            ->map(fn($i) => Transaksi::whereIn('status', [
                StatusTransaksi::Berjalan,
                StatusTransaksi::Dibayar,
            ])->whereDate('updated_at', now()->subDays($i))->count())
            ->toArray();

        // ── ④ TERLAMBAT + DENDA ─────────────────────────────────────────
        $terlambat          = Transaksi::where('status', StatusTransaksi::Terlambat)->count();
        $dendaBelumLunas    = Denda::whereNull('dibayar_pada')->sum('jumlah');
        $dendaKerusakan     = Denda::whereNull('dibayar_pada')->where('jenis', 'kerusakan')->sum('jumlah');
        $dendaKeterlambatan = Denda::whereNull('dibayar_pada')->where('jenis', 'terlambat')->sum('jumlah');

        $trendTerlambat = collect(range(6, 0))
            ->map(fn($i) => Transaksi::where('status', StatusTransaksi::Terlambat)
                ->whereDate('updated_at', now()->subDays($i))->count())
            ->toArray();

        return [

            // ① TOTAL TRANSAKSI
            Stat::make('Total Transaksi', number_format($total, 0, ',', '.'))
                ->description(implode(' • ', [
                    $selisihHarian > 0
                        ? "↑ +" . abs($selisihHarian) . " hari ini"
                        : ($selisihHarian < 0
                            ? "↓ " . abs($selisihHarian) . " hari ini"
                            : "Sama seperti kemarin"),
                    "Bulan ini: {$bulanIni}",
                ]))
                ->descriptionIcon(
                    $selisihHarian >= 0
                        ? 'heroicon-m-arrow-trending-up'
                        : 'heroicon-m-arrow-trending-down',
                    'before'
                )
                ->chart($trendTotal)
                ->chartColor('primary')
                ->color('primary'),

            // ② MENUNGGU PEMBAYARAN
            Stat::make('Menunggu Pembayaran', $menunggu)
                ->description(implode('   •   ', array_filter([
                    'Nilai: Rp ' . number_format($nilaiMenunggu, 0, ',', '.'),
                    "Midtrans: {$midtrans} | Tunai: {$tunai}",
                    $terlambatBayar > 0 ? "⚠ Lewat jatuh tempo: {$terlambatBayar}" : null,
                ])))
                ->descriptionIcon('heroicon-m-credit-card', 'before')
                ->chart($trendMenunggu)
                ->chartColor($menunggu > 0 ? 'warning' : 'success')
                ->color($menunggu > 0 ? 'warning' : 'success'),

            // ③ TRANSAKSI AKTIF / BERJALAN
            Stat::make('Transaksi Aktif', $aktif)
                ->description(implode('   •   ', array_filter([
                    "Berjalan: {$berjalan} | Siap ambil: {$siapAmbil}",
                    $jatuhTempoHariIni > 0 ? "⚠ Kembali hari ini: {$jatuhTempoHariIni}" : null,
                    $jatuhTempoBesok > 0   ? "Besok: {$jatuhTempoBesok}" : null,
                ])))
                ->descriptionIcon('heroicon-m-play-circle', 'before')
                ->chart($trendAktif)
                ->chartColor('info')
                ->color('info'),

            // ④ TERLAMBAT + DENDA
            Stat::make('Terlambat', $terlambat)
                ->description(implode('   •   ', array_filter([
                    'Tagihan: Rp ' . number_format($dendaBelumLunas, 0, ',', '.'),
                    $dendaKerusakan > 0
                        ? 'Kerusakan: Rp ' . number_format($dendaKerusakan, 0, ',', '.')
                        : null,
                    $dendaKeterlambatan > 0
                        ? 'Keterlambatan: Rp ' . number_format($dendaKeterlambatan, 0, ',', '.')
                        : null,
                ])))
                ->descriptionIcon('heroicon-m-exclamation-triangle', 'before')
                ->chart($trendTerlambat)
                ->chartColor('danger')
                ->color($terlambat > 0 ? 'danger' : 'success'),

        ];
    }
}
