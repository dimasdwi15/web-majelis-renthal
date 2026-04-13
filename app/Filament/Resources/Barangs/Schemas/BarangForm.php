<?php

namespace App\Filament\Resources\Barangs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use App\Models\KategoriBarang;

class BarangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kategori_barang_id')
                    ->label('Kategori')
                    ->options(
                        KategoriBarang::where('aktif', 1)->pluck('nama', 'id')
                    )
                    ->searchable()
                    ->required(),

                TextInput::make('nama')
                    ->required(),

                Textarea::make('deskripsi')
                    ->columnSpanFull(),

                Textarea::make('spesifikasi')
                    ->columnSpanFull(),

                TextInput::make('harga_per_hari')
                    ->numeric()
                    ->required(),

                TextInput::make('stok')
                    ->numeric()
                    ->required(),

                Select::make('status')
                    ->options([
                        'aktif' => 'Ready',
                        'nonaktif' => 'Maintenance',
                    ])
                    ->default('aktif')
                    ->required(),

                FileUpload::make('foto')
                    ->label('Foto Barang')
                    ->image()
                    ->multiple()
                    ->directory('barang')
                    ->disk('public')
                    ->visibility('public'),
            ]);
    }
}
