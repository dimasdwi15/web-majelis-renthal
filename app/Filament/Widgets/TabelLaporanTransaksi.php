<?php

namespace App\Filament\Widgets;

use App\Models\Transaksi;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TabelLaporanTransaksi extends BaseWidget
{
    protected static ?string $heading = 'Rincian Transaksi';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaksi::query()
                    ->with(['user', 'transaksiDetail.barang'])
                    ->whereIn('status', ['selesai', 'berjalan', 'dikembalikan', 'terlambat'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('nomor_transaksi')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelanggan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_sewa')
                    ->label('Pendapatan Sewa')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_denda')
                    ->label('Pendapatan Denda')
                    ->formatStateUsing(fn($state) => $state > 0
                        ? 'Rp ' . number_format($state, 0, ',', '.')
                        : '-')
                    ->color(fn($state) => $state > 0 ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('total_charge')
                    ->label('Total Diterima')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->badge()
                    ->color(fn($state) => match ($state?->value ?? $state) {
                        'midtrans' => 'info',
                        'tunai'    => 'success',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state?->value ?? $state) {
                        'selesai'      => 'success',
                        'berjalan'     => 'info',
                        'terlambat'    => 'danger',
                        'dikembalikan' => 'warning',
                        default        => 'gray',
                    }),

                Tables\Columns\TextColumn::make('tanggal_ambil')
                    ->label('Tgl Ambil')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('tanggal_kembali')
                    ->label('Tgl Kembali')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Transaksi')
                    ->options([
                        'selesai'      => 'Selesai',
                        'berjalan'     => 'Berjalan',
                        'terlambat'    => 'Terlambat',
                        'dikembalikan' => 'Dikembalikan',
                    ]),

                Tables\Filters\SelectFilter::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->options([
                        'midtrans' => 'Midtrans',
                        'tunai'    => 'Tunai',
                    ]),

                Tables\Filters\Filter::make('bulan_ini')
                    ->label('Bulan Ini')
                    ->query(fn(Builder $query) => $query
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year))
                    ->default(),

                Tables\Filters\Filter::make('tahun_ini')
                    ->label('Tahun Ini')
                    ->query(fn(Builder $query) => $query
                        ->whereYear('created_at', now()->year)),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
