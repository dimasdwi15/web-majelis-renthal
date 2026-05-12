<?php

namespace App\Filament\Resources\BarangRusaks\Tables;

use App\Models\BarangRusak;
use App\Services\TransaksiService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BarangRusaksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('barang.nama')
                    ->label('Barang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jumlah')
                    ->label('Qty')
                    ->suffix(' unit')
                    ->sortable(),

                TextColumn::make('transaksi.nomor_transaksi')
                    ->label('Transaksi')
                    ->searchable()
                    ->url(fn (BarangRusak $record) => route('filament.admin.resources.transaksis.view', ['record' => $record->transaksi_id]))
                    ->color('primary'),

                TextColumn::make('transaksi.user.name')
                    ->label('Penyewa')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === BarangRusak::STATUS_MENUNGGU
                        ? 'Menunggu perbaikan'
                        : 'Sudah diperbaiki')
                    ->color(fn (string $state) => $state === BarangRusak::STATUS_MENUNGGU ? 'warning' : 'success'),

                TextColumn::make('catatan_kerusakan')
                    ->label('Catatan')
                    ->limit(40)
                    ->tooltip(fn (BarangRusak $record) => $record->catatan_kerusakan)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dicatat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        BarangRusak::STATUS_MENUNGGU => 'Menunggu perbaikan',
                        BarangRusak::STATUS_DIPERBAIKI => 'Sudah diperbaiki',
                    ]),
            ])
            ->recordActions([
                Action::make('kembalikanKeStok')
                    ->label('Perbaiki & stok sewa')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Kembalikan ke stok sewa?')
                    ->modalDescription(
                        'Stok barang akan bertambah sesuai qty rusak. Pastikan unit sudah benar-benar siap disewa lagi.'
                    )
                    ->visible(fn (BarangRusak $record) => $record->status === BarangRusak::STATUS_MENUNGGU)
                    ->action(function (BarangRusak $record) {
                        app(TransaksiService::class)->kembalikanBarangRusakKeStok($record);

                        Notification::make()
                            ->title('Stok diperbarui')
                            ->body('Barang ditambahkan ke stok sewa dan status diperbarui.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
