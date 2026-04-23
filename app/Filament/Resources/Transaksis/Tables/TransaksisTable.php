<?php

namespace App\Filament\Resources\Transaksis\Tables;

use App\Enums\StatusTransaksi;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class TransaksisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([

                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('nomor_transaksi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('created_at')
                    ->label('Masuk')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since()          
                    ->tooltip(fn($record) => $record->created_at->format('d M Y, H:i:s'))
                    ->color('gray'),

                TextColumn::make('user.name')
                    ->label('Penyewa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof \App\Enums\MetodePembayaran ? $state->label() : $state)
                    ->colors([
                        'info'    => 'midtrans',
                        'success' => 'tunai',
                    ]),

                TextColumn::make('tanggal_ambil')
                    ->label('Ambil')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('tanggal_kembali')
                    ->label('Kembali')
                    ->date('d M Y')
                    ->sortable()
                    // Warna merah jika tanggal kembali sudah lewat dan status masih berjalan/terlambat
                    ->color(function ($record) {
                        if (
                            in_array(
                                $record->status instanceof StatusTransaksi ? $record->status->value : $record->status,
                                ['berjalan', 'terlambat']
                            )
                            && now()->startOfDay()->isAfter(\Carbon\Carbon::parse($record->tanggal_kembali)->startOfDay())
                        ) {
                            return 'danger';
                        }
                        return null;
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function ($state, $record) {
                        // Auto-detect terlambat di level display:
                        // Jika status masih 'berjalan' tapi tanggal_kembali sudah lewat,
                        // tampilkan badge "Terlambat" meski DB belum diupdate oleh scheduler.
                        $statusValue = $state instanceof StatusTransaksi ? $state->value : $state;

                        if (
                            $statusValue === 'berjalan'
                            && now()->startOfDay()->isAfter(\Carbon\Carbon::parse($record->tanggal_kembali)->startOfDay())
                        ) {
                            return StatusTransaksi::Terlambat->label();
                        }

                        return $state instanceof StatusTransaksi ? $state->label() : $state;
                    })
                    ->colors([
                        'warning' => fn($state, $record) => in_array(
                            $state instanceof StatusTransaksi ? $state->value : $state,
                            ['menunggu_pembayaran', 'dikembalikan']
                        ),
                        'info'    => fn($state) => ($state instanceof StatusTransaksi ? $state->value : $state) === 'dibayar',
                        'primary' => fn($state, $record) => (
                            ($state instanceof StatusTransaksi ? $state->value : $state) === 'berjalan'
                            && !now()->startOfDay()->isAfter(\Carbon\Carbon::parse($record->tanggal_kembali)->startOfDay())
                        ),
                        'danger'  => fn($state, $record) => (
                            ($state instanceof StatusTransaksi ? $state->value : $state) === 'terlambat'
                            || (
                                ($state instanceof StatusTransaksi ? $state->value : $state) === 'berjalan'
                                && now()->startOfDay()->isAfter(\Carbon\Carbon::parse($record->tanggal_kembali)->startOfDay())
                            )
                        ),
                        'success' => fn($state) => ($state instanceof StatusTransaksi ? $state->value : $state) === 'selesai',
                        'gray'    => fn($state) => ($state instanceof StatusTransaksi ? $state->value : $state) === 'dibatalkan',
                    ]),

                TextColumn::make('total_sewa')
                    ->label('Total Sewa')
                    ->money('IDR')
                    ->sortable(),

                // Kolom denda — hanya tampil jika ada denda
                TextColumn::make('total_denda')
                    ->label('Total Denda')
                    ->money('IDR')
                    ->sortable()
                    ->color('danger')
                    ->weight('bold')
                    ->visible(fn() => true) // selalu tampil, nilai 0 akan tampil sebagai Rp 0
                    ->formatStateUsing(
                        fn($state) => $state > 0
                            ? 'Rp ' . number_format($state, 0, ',', '.')
                            : '—'
                    ),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->options(
                        collect(StatusTransaksi::cases())
                            ->mapWithKeys(fn($s) => [$s->value => $s->label()])
                            ->toArray()
                    ),
                SelectFilter::make('metode_pembayaran')
                    ->label('Metode')
                    ->options([
                        'tunai'    => 'Tunai (COD)',
                        'midtrans' => 'Cashless (Midtrans)',
                    ]),
            ])

            ->recordActions([
                ViewAction::make(),
            ])
            ->recordUrl(fn($record) => url('/admin/transaksis/' . $record->id))

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
