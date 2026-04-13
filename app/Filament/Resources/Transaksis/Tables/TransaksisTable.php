<?php

namespace App\Filament\Resources\Transaksis\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class TransaksisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_transaksi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Penyewa')
                    ->searchable(),

                TextColumn::make('tanggal_ambil')
                    ->date(),

                TextColumn::make('tanggal_kembali')
                    ->date(),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'menunggu_pembayaran',
                        'info' => 'dibayar',
                        'primary' => 'berjalan',
                        'danger' => 'terlambat',
                        'success' => 'selesai',
                    ]),

                TextColumn::make('total_sewa')
                    ->money('IDR'),
            ])

            // 🔥 FIX URL
            ->recordUrl(fn ($record) => url(
                '/admin/transaksis/' . $record->id
            ))

            ->recordActions([
                ViewAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
