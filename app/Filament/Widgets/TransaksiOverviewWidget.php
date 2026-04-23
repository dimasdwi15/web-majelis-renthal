<?php

namespace App\Filament\Widgets;

use App\Enums\StatusTransaksi;
use App\Models\Barang;
use App\Models\Denda;
use App\Models\Transaksi;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TransaksiOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Polling 30 detik — responsif tanpa membebani DB
    protected ?string $pollingInterval = '30s';

    // Full width agar 4 kolom muat nyaman
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    // ── Helper: sparkline harian ───────────────────────────────────────
    private function sparklineHarian(string $table, string $kolom = 'created_at', int $hari = 7): array
    {
        return collect(range($hari - 1, 0))
            ->map(fn($i) => (int) DB::table($table)
                ->whereDate($kolom, now()->subDays($i)->toDateString())
                ->count()
            )
            ->toArray();
    }

    private function sparklinePendapatan(int $hari = 7): array
    {
        $statusBerhasil = [
            StatusTransaksi::Selesai->value,
            StatusTransaksi::Berjalan->value,
            StatusTransaksi::Terlambat->value,
            StatusTransaksi::Dikembalikan->value,
        ];

        return collect(range($hari - 1, 0))
            ->map(fn($i) => (float) DB::table('transaksi')
                ->whereIn('status', $statusBerhasil)
                ->whereDate('created_at', now()->subDays($i)->toDateString())
                ->sum('total_sewa')
            )
            ->toArray();
    }

    // Format rupiah singkat: 1.500.000 → Rp 1,5 Jt
    private function rupiah(float $angka): string
    {
        if ($angka >= 1_000_000_000) {
            return 'Rp ' . number_format($angka / 1_000_000_000, 1, ',', '.') . ' M';
        }
        if ($angka >= 1_000_000) {
            return 'Rp ' . number_format($angka / 1_000_000, 1, ',', '.') . ' Jt';
        }
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }

    protected function getStats(): array
    {
        $now      = now();
        $bulanIni = $now->month;
        $tahunIni = $now->year;
        $bulanLalu = $now->copy()->subMonth();

        // ── Operasional ────────────────────────────────────────────────
        $menunggu     = Transaksi::where('status', StatusTransaksi::MenungguPembayaran)->count();
        $berjalan     = Transaksi::whereIn('status', [StatusTransaksi::Berjalan, StatusTransaksi::Dibayar])->count();
        $terlambat    = Transaksi::where('status', StatusTransaksi::Terlambat)->count();
        $dikembalikan = Transaksi::where('status', StatusTransaksi::Dikembalikan)->count();

        // ── Keuangan ───────────────────────────────────────────────────
        $statusBerhasil = [
            StatusTransaksi::Selesai->value,
            StatusTransaksi::Berjalan->value,
            StatusTransaksi::Terlambat->value,
            StatusTransaksi::Dikembalikan->value,
        ];

        $pendapatanBulanIni = (float) DB::table('transaksi')
            ->whereIn('status', $statusBerhasil)
            ->whereMonth('created_at', $bulanIni)
            ->whereYear('created_at', $tahunIni)
            ->sum('total_sewa');

        $pendapatanBulanLalu = (float) DB::table('transaksi')
            ->whereIn('status', $statusBerhasil)
            ->whereMonth('created_at', $bulanLalu->month)
            ->whereYear('created_at', $bulanLalu->year)
            ->sum('total_sewa');

        $pctPendapatan = $pendapatanBulanLalu > 0
            ? round((($pendapatanBulanIni - $pendapatanBulanLalu) / $pendapatanBulanLalu) * 100, 1)
            : ($pendapatanBulanIni > 0 ? 100.0 : 0.0);

        $pendapatanAllTime = (float) DB::table('transaksi')
            ->whereIn('status', $statusBerhasil)
            ->sum('total_sewa');

        $totalDendaAllTime = (float) DB::table('transaksi')->sum('total_denda');
        $dendaBelumLunas   = (float) Denda::whereNull('dibayar_pada')->sum('jumlah');
        $jumlahDendaAktif  = Denda::whereNull('dibayar_pada')->count();

        // ── Transaksi harian ───────────────────────────────────────────
        $transaksiHariIni = Transaksi::whereDate('created_at', $now->toDateString())->count();
        $transaksiKemarin = Transaksi::whereDate('created_at', $now->copy()->subDay()->toDateString())->count();
        $diffHarian       = $transaksiHariIni - $transaksiKemarin;

        $selesai         = Transaksi::where('status', StatusTransaksi::Selesai)->count();
        $selesaiBulanIni = Transaksi::where('status', StatusTransaksi::Selesai)
            ->whereMonth('updated_at', $bulanIni)
            ->whereYear('updated_at', $tahunIni)
            ->count();

        // ── Inventaris ────────────────────────────────────────────────
        $barangAktif      = Barang::where('status', 'aktif')->count();
        $barangNonaktif   = Barang::where('status', 'nonaktif')->count();
        $barangStokRendah = Barang::where('status', 'aktif')->where('stok', '<=', 2)->count();
        $totalStok        = Barang::where('status', 'aktif')->sum('stok');

        // ── User ───────────────────────────────────────────────────────
        $totalUser     = User::where('role', 'user')->count();
        $userBaru      = User::where('role', 'user')
            ->whereMonth('created_at', $bulanIni)
            ->whereYear('created_at', $tahunIni)
            ->count();
        $userBulanLalu = User::where('role', 'user')
            ->whereMonth('created_at', $bulanLalu->month)
            ->whereYear('created_at', $bulanLalu->year)
            ->count();

        // ── Sparklines ────────────────────────────────────────────────
        $sparklineTransaksi  = $this->sparklineHarian('transaksi');
        $sparklinePendapatan = $this->sparklinePendapatan();
        $sparklineMenunggu   = collect(range(6, 0))
            ->map(fn($i) => (int) DB::table('transaksi')
                ->where('status', StatusTransaksi::MenungguPembayaran->value)
                ->whereDate('created_at', now()->subDays($i)->toDateString())
                ->count()
            )->toArray();
        $sparklineSelesai = collect(range(6, 0))
            ->map(fn($i) => (int) DB::table('transaksi')
                ->where('status', StatusTransaksi::Selesai->value)
                ->whereDate('updated_at', now()->subDays($i)->toDateString())
                ->count()
            )->toArray();
        $sparklineUser = $this->sparklineHarian('users');

        return [
            // ══ BARIS 1: STATUS OPERASIONAL ══════════════════════════

            Stat::make('⏳ Menunggu Pembayaran', $menunggu)
                ->description('Transaksi baru perlu diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->chart($sparklineMenunggu)
                ->color('warning')
                ->url('/admin/transaksis?tableFilters[status][value]=menunggu_pembayaran'),

            Stat::make('🚚 Sedang Berjalan', $berjalan)
                ->description('Barang aktif di tangan penyewa')
                ->descriptionIcon('heroicon-m-truck')
                ->chart($sparklineTransaksi)
                ->color('primary'),

            Stat::make('⚠️ Terlambat', $terlambat)
                ->description($terlambat > 0 ? 'Segera tindaklanjuti!' : 'Tidak ada keterlambatan')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($terlambat > 0 ? 'danger' : 'success')
                ->url('/admin/transaksis?tableFilters[status][value]=terlambat'),

            Stat::make('↩️ Dikembalikan', $dikembalikan)
                ->description($dikembalikan > 0 ? 'Menunggu penyelesaian denda' : 'Tidak ada yang pending')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color($dikembalikan > 0 ? 'warning' : 'success')
                ->url('/admin/transaksis?tableFilters[status][value]=dikembalikan'),

            // ══ BARIS 2: KEUANGAN ════════════════════════════════════

            Stat::make('💰 Pendapatan ' . $now->translatedFormat('F Y'),
                $this->rupiah($pendapatanBulanIni)
            )
                ->description(
                    ($pctPendapatan >= 0 ? '↑ ' : '↓ ') .
                    abs($pctPendapatan) . '% vs ' . $bulanLalu->translatedFormat('F')
                )
                ->descriptionIcon($pctPendapatan >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($sparklinePendapatan)
                ->color($pctPendapatan >= 0 ? 'success' : 'danger'),

            Stat::make('🏦 Total Pendapatan (All Time)', $this->rupiah($pendapatanAllTime))
                ->description('Dari ' . Transaksi::count() . ' total transaksi')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->chart($sparklineSelesai)
                ->color('success'),

            // ══ BARIS 3: INVENTARIS & USER ════════════════════════════

            Stat::make('📋 Transaksi Hari Ini', $transaksiHariIni)
                ->description(
                    $diffHarian === 0
                        ? 'Sama seperti kemarin'
                        : ($diffHarian > 0 ? '↑ ' : '↓ ') . abs($diffHarian) . ' vs kemarin'
                )
                ->descriptionIcon('heroicon-m-calendar-days')
                ->chart($sparklineTransaksi)
                ->color($diffHarian >= 0 ? 'primary' : 'warning'),

            Stat::make('🔴 Stok Rendah', $barangStokRendah)
                ->description($barangStokRendah > 0
                    ? 'Stok ≤ 2 unit — perlu restok segera'
                    : 'Semua stok dalam kondisi aman'
                )
                ->descriptionIcon($barangStokRendah > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-badge')
                ->color($barangStokRendah > 0 ? 'danger' : 'success'),

        ];
    }
}
