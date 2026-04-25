<?php

namespace App\Filament\Resources\Barangs\Schemas;

use App\Filament\Resources\Barangs\Actions\GenerateBarangAction;
use App\Models\KategoriBarang;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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

                // ── Nama Barang + Tombol Auto Generate ────────────────────────
                TextInput::make('nama')
                    ->label('Nama Barang')
                    ->required()
                    ->placeholder('Contoh: Carrier 60L Deuter, Tenda Dome 4 orang...')
                    ->helperText('Isi nama barang lalu klik ✨ Auto Generate untuk mengisi deskripsi & spesifikasi otomatis.')
                    // ✅ FIX: hapus ->disabled() dari action karena pakai $get, bukan $state
                    ->suffixActions([
                        GenerateBarangAction::make(),
                    ]),

                Textarea::make('deskripsi')
                    ->label('Deskripsi')
                    ->placeholder('Klik ✨ Auto Generate di atas, atau tulis deskripsi secara manual di sini.')
                    ->rows(4)
                    ->columnSpanFull(),

                Textarea::make('spesifikasi')
                    ->label('Spesifikasi Teknis')
                    ->placeholder("- Material: ...\n- Ukuran: ...\n- Berat: ...\n- Kapasitas: ...\n- Fitur utama: ...")
                    ->rows(6)
                    ->columnSpanFull(),

                TextInput::make('harga_per_hari')
                    ->label('Harga per Hari (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                TextInput::make('stok')
                    ->label('Stok')
                    ->numeric()
                    ->minValue(0)
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'aktif'    => 'Ready',
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
                    ->visibility('public')
                    ->columnSpanFull(),
            ]);
    }
}
