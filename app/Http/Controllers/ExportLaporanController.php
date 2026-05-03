<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ExportLaporanController extends Controller
{
    public function export()
    {
        $rows = Transaksi::select(
            'id',
            'total_sewa',
            'total_denda',
            'status',
            'created_at'
        )
        ->orderBy('created_at', 'desc')
        ->get();

        $writer = SimpleExcelWriter::streamDownload(
            'laporan-keuangan.xlsx'
        );

        /**
         * Header laporan
         */
        $writer->addRow([
            'LAPORAN KEUANGAN MAJELIS RENTAL'
        ]);

        $writer->addRow([
            'Tanggal Export : ' . now()->format('d-m-Y H:i:s')
        ]);

        $writer->addRow([
            'Total Data : ' . $rows->count() . ' transaksi'
        ]);

        /**
         * Baris kosong agar rapi
         */
        $writer->addRow([]);
        $writer->addRow([]);

        /**
         * Header tabel
         */
        $writer->addRow([
            'No',
            'ID Transaksi',
            'Total Sewa',
            'Total Denda',
            'Total Keseluruhan',
            'Status',
            'Tanggal Transaksi',
        ]);

        $no = 1;

        foreach ($rows as $row) {
            $status = is_object($row->status)
                ? $row->status->value
                : $row->status;

            $totalKeseluruhan = $row->total_sewa + $row->total_denda;

            $writer->addRow([
                'No' => $no++,
                'ID Transaksi' => $row->id,
                'Total Sewa' => 'Rp ' . number_format($row->total_sewa, 0, ',', '.'),
                'Total Denda' => 'Rp ' . number_format($row->total_denda, 0, ',', '.'),
                'Total Keseluruhan' => 'Rp ' . number_format($totalKeseluruhan, 0, ',', '.'),
                'Status' => ucfirst($status),
                'Tanggal Transaksi' => $row->created_at?->format('d-m-Y H:i:s'),
            ]);
        }

        /**
         * Footer ringkasan
         */
        $writer->addRow([]);
        $writer->addRow([
            'Export selesai'
        ]);

        return $writer->toBrowser();
    }
}
