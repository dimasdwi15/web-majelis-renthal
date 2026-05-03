<?php

namespace App\Exports;

use App\Models\Transaksi;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanKeuanganExport
{
    public function __construct(
        protected string $bulan,
        protected string $tahun
    ) {}

    /**
     * Download laporan keuangan sebagai file .xlsx
     * menggunakan spatie/simple-excel (kompatibel PHP 8.5+).
     */
    public function download(): StreamedResponse
    {
        $namaFile = 'laporan-keuangan-' . $this->tahun . '-' . $this->bulan . '.xlsx';

        $transaksi = Transaksi::with(['user', 'transaksiDetail.barang'])
            ->whereYear('created_at', $this->tahun)
            ->whereMonth('created_at', $this->bulan)
            ->whereIn('status', ['selesai', 'berjalan', 'dikembalikan', 'terlambat'])
            ->orderBy('created_at')
            ->get();

        return response()->streamDownload(function () use ($transaksi) {

            $writer = SimpleExcelWriter::streamDownload('laporan.xlsx');

            // Tulis header
            $writer->addRow([
                'No. Transaksi',
                'Pelanggan',
                'Barang Disewa',
                'Tgl Ambil',
                'Tgl Kembali',
                'Metode Pembayaran',
                'Status',
                'Pendapatan Sewa (Rp)',
                'Pendapatan Denda (Rp)',
                'Total Diterima (Rp)',
            ]);

            // Tulis setiap baris data
            foreach ($transaksi as $trx) {
                $namaBarang = $trx->transaksiDetail
                    ->map(fn ($d) => $d->barang->nama . ' x' . $d->jumlah)
                    ->join(', ');

                $writer->addRow([
                    $trx->nomor_transaksi,
                    $trx->user->name,
                    $namaBarang,
                    $trx->tanggal_ambil,
                    $trx->tanggal_kembali,
                    ucfirst($trx->metode_pembayaran),
                    ucfirst(str_replace('_', ' ', $trx->status)),
                    (float) $trx->total_sewa,
                    (float) $trx->total_denda,
                    (float) $trx->total_charge,
                ]);
            }

            $writer->toBrowser();

        }, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
