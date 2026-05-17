<?php

namespace App\Filament\Resources\BarangRusaks\Tables;

use App\Models\BarangRusak;
use App\Services\TransaksiService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BarangRusaksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                // ── Foto barang ────────────────────────────────────────────
                ImageColumn::make('barang.fotoUtama.path_foto')
                    ->label('Foto')
                    ->disk('public')
                    ->height(64)
                    ->width(64)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->defaultImageUrl(
                        fn () => 'https://placehold.co/64x64/e5e7eb/9ca3af?text=No+Foto'
                    ),

                // ── Nama barang ────────────────────────────────────────────
                TextColumn::make('barang.nama')
                    ->label('Barang')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // ── Jumlah unit rusak ──────────────────────────────────────
                TextColumn::make('jumlah')
                    ->label('Qty')
                    ->suffix(' unit')
                    ->sortable(),

                // ── Link ke transaksi asal ─────────────────────────────────
                TextColumn::make('transaksi.nomor_transaksi')
                    ->label('Transaksi')
                    ->searchable()
                    ->url(
                        fn (BarangRusak $record) => route(
                            'filament.admin.resources.transaksis.view',
                            ['record' => $record->transaksi_id]
                        )
                    )
                    ->color('primary'),

                // ── Nama penyewa ───────────────────────────────────────────
                TextColumn::make('transaksi.user.name')
                    ->label('Penyewa')
                    ->toggleable(),

                // ── Status perbaikan ───────────────────────────────────────
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state) => $state === BarangRusak::STATUS_MENUNGGU
                            ? 'Menunggu perbaikan'
                            : 'Sudah diperbaiki'
                    )
                    ->color(
                        fn (string $state) => $state === BarangRusak::STATUS_MENUNGGU
                            ? 'warning'
                            : 'success'
                    ),

                // ── Catatan kerusakan ──────────────────────────────────────
                TextColumn::make('catatan_kerusakan')
                    ->label('Catatan')
                    ->limit(40)
                    ->tooltip(fn (BarangRusak $record) => $record->catatan_kerusakan)
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Tanggal dicatat ────────────────────────────────────────
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
                        BarangRusak::STATUS_MENUNGGU    => 'Menunggu perbaikan',
                        BarangRusak::STATUS_DIPERBAIKI  => 'Sudah diperbaiki',
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
                        'Stok barang akan bertambah sesuai qty rusak. '
                        .'Pastikan unit sudah benar-benar siap disewa lagi.'
                    )
                    ->visible(
                        fn (BarangRusak $record) => $record->status === BarangRusak::STATUS_MENUNGGU
                    )
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
