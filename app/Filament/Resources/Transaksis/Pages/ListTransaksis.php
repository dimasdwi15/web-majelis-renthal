<?php

namespace App\Filament\Resources\Transaksis\Pages;

use App\Filament\Resources\Transaksis\TransaksiResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Transaksis\Widgets\TransaksiStats;

class ListTransaksis extends ListRecords
{
    protected static string $resource = TransaksiResource::class;

    // ── Header Actions ────────────────────────────────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            Action::make('scanQrCode')
                ->label('Scan QR Transaksi')
                ->icon('heroicon-o-qr-code')
                ->color('primary')
                ->modalHeading('Scan QR Code Transaksi')
                ->modalIcon('heroicon-o-qr-code')
                ->modalDescription(
                    'Arahkan kamera ke QR Code nomor transaksi untuk membuka detail transaksi secara otomatis.'
                )
                ->modalContent(fn () => view('filament.modals.qr-scanner'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalWidth('lg'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
          TransaksiStats::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }
}
