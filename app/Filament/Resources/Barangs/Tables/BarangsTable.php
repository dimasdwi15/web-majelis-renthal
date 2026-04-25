<?php

namespace App\Filament\Resources\Barangs\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

use App\Models\BarangFoto;
use App\Models\KategoriBarang;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Actions\DeleteBulkAction;

class BarangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('nama')
                    ->searchable(),

                ImageColumn::make('fotoUtama.path_foto')
                    ->label('Foto')
                    ->getStateUsing(
                        fn($record) =>
                        $record->fotoUtama
                            ? asset('storage/' . $record->fotoUtama->path_foto)
                            : asset('no-image.png')
                    )
                    ->imageHeight(75)
                    ->imageWidth(75)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),

                TextColumn::make('kategori.nama')
                    ->label('Kategori')
                    ->badge(),

                // ── Kolom Tags ─────────────────────────────────────────────────
                // Menampilkan semua tag yang di-assign ke barang ini.
                // Jika kosong berarti barang belum di-tag → tidak akan muncul
                // di rekomendasi cuaca manapun.
                TextColumn::make('tags.label')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->placeholder('— belum ada tag —')
                    ->color('warning'),

                TextColumn::make('stok')
                    ->suffix(' Unit'),

                TextColumn::make('harga_per_hari')
                    ->money('IDR', locale: 'id'),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'aktif',
                        'warning' => 'disewa',
                        'danger'  => 'nonaktif',
                    ]),
            ])

            ->filters([
                SelectFilter::make('kategori_barang_id')
                    ->label('Kategori')
                    ->options(
                        KategoriBarang::pluck('nama', 'id')
                    ),

                SelectFilter::make('status')
                    ->options([
                        'aktif'    => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                    ]),

                // ── Filter by Tag ──────────────────────────────────────────────
                // Admin bisa filter tabel barang berdasarkan tag fungsional.
                SelectFilter::make('tags')
                    ->label('Tag Fungsional')
                    ->relationship('tags', 'label')
                    ->searchable()
                    ->preload(),

                Filter::make('stok')
                    ->form([
                        TextInput::make('min')
                            ->label('Min Stok')
                            ->numeric(),

                        TextInput::make('max')
                            ->label('Max Stok')
                            ->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['min'], fn($q) => $q->where('stok', '>=', $data['min']))
                            ->when($data['max'], fn($q) => $q->where('stok', '<=', $data['max']));
                    }),
            ])

            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit Barang')
                    ->modalSubmitActionLabel('Update')
                    ->after(function ($record, array $data) {
                        // Handle foto: replace semua foto jika ada upload baru
                        if (array_key_exists('foto', $data) && !empty($data['foto'])) {
                            $record->foto()->delete();

                            foreach ($data['foto'] as $path) {
                                BarangFoto::create([
                                    'barang_id' => $record->id,
                                    'path_foto' => $path,
                                ]);
                            }
                        }

                        // CATATAN: tags TIDAK perlu dihandle di sini.
                        // Filament sync pivot barang_tag secara otomatis
                        // karena Select::make('tags')->relationship() di BarangForm.
                    }),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
