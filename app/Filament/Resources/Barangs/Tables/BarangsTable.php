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
use Illuminate\Support\HtmlString;



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

                TextColumn::make('stok')
                    ->suffix(' Unit'),

                TextColumn::make('harga_per_hari')
                    ->money('IDR', locale: 'id'),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'aktif',
                        'warning' => 'disewa',
                        'danger' => 'nonaktif',
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
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                    ]),

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
                            ->when($data['min'], fn ($q) => $q->where('stok', '>=', $data['min']))
                            ->when($data['max'], fn ($q) => $q->where('stok', '<=', $data['max']));
                    }),

                // FILTER HARGA RANGE

                // Filter::make('harga_per_hari')
                //     ->form([
                //         TextInput::make('min')
                //             ->label('Min Harga')
                //             ->numeric(),

                //         TextInput::make('max')
                //             ->label('Max Harga')
                //             ->numeric(),
                //     ])
                //     ->query(function ($query, array $data) {
                //         return $query
                //             ->when($data['min'], fn ($q) => $q->where('harga_per_hari', '>=', $data['min']))
                //             ->when($data['max'], fn ($q) => $q->where('harga_per_hari', '<=', $data['max']));
                //     }),

            ])

            // EDIT + DELETE
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit Barang')
                    ->modalSubmitActionLabel('Update')

                    ->after(function ($record, array $data) {
                        if (array_key_exists('foto', $data)) {

                            if (!empty($data['foto'])) {

                                $record->foto()->delete();

                                foreach ($data['foto'] as $path) {
                                    BarangFoto::create([
                                        'barang_id' => $record->id,
                                        'path_foto' => $path,
                                    ]);
                                }
                            }
                        }
                    }),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
