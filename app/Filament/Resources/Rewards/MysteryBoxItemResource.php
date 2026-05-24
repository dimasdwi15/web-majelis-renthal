<?php

namespace App\Filament\Resources\Rewards;

use App\Models\MysteryBoxItem;
use App\Models\Barang;
use App\Models\BarangFoto;
use App\Filament\Resources\Rewards\Pages\ListMysteryBoxItems;
use App\Filament\Resources\Rewards\Pages\CreateMysteryBoxItem;
use App\Filament\Resources\Rewards\Pages\EditMysteryBoxItem;
use BackedEnum;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Support\Facades\Storage;

class MysteryBoxItemResource extends Resource
{
    protected static ?string $model = MysteryBoxItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';
    protected static string|\UnitEnum|null  $navigationGroup = 'Rewards';
    protected static ?string $navigationLabel = 'Mystery Box Items';
    protected static ?string $modelLabel      = 'Mystery Box Item';
    protected static ?string $pluralModelLabel = 'Mystery Box Items';
    protected static ?int $navigationSort  = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            // ── Informasi utama ───────────────────────────────────────────────
            Section::make('🎁 Informasi Hadiah Mystery Box')
                ->description('Item ini akan masuk ke pool acak mystery box. User akan mendapatkan item ini secara acak saat membuka mystery box.')
                ->icon('heroicon-o-gift')
                ->schema([
                    Select::make('type')
                        ->label('Tipe Hadiah')
                        ->options([
                            'discount'     => '💰 Diskon Harga (potongan Rp)',
                            'free_rental'  => '🎒 Gratis Sewa Barang',
                        ])
                        ->default('discount')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('barang_id', null);
                            $set('barang_nama_snapshot', null);
                            $set('barang_foto_url_snapshot', null);
                            $set('discount_amount', 0);
                            $set('min_checkout', 0);
                        }),

                    Select::make('rarity')
                        ->label('Kelangkaan')
                        ->options([
                            'Common' => '⬜ Common — Sering keluar',
                            'Rare'   => '🔵 Rare — Jarang keluar',
                            'Epic'   => '🟣 Epic — Sangat jarang',
                        ])
                        ->default('Common')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            $weight = match ($state) {
                                'Epic'  => 10,
                                'Rare'  => 40,
                                default => 100,
                            };
                            $set('weight', $weight);
                        }),

                    // ── Field discount — hanya tampil saat type = discount ────
                    TextInput::make('title')
                        ->label('Judul Hadiah')
                        ->required()
                        ->placeholder('Contoh: Diskon Rp50.000')
                        ->maxLength(100)
                        ->visible(fn (Get $get) => $get('type') === 'discount'),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->placeholder('Contoh: Potongan harga Rp50.000 untuk sewa apapun')
                        ->rows(2)
                        ->visible(fn (Get $get) => $get('type') === 'discount'),

                    Grid::make(2)
                        ->visible(fn (Get $get) => $get('type') === 'discount')
                        ->schema([
                            TextInput::make('discount_amount')
                                ->label('Jumlah Potongan (Rp)')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->prefix('Rp')
                                ->live(onBlur: true),

                            TextInput::make('min_checkout')
                                ->label('Minimum Checkout (Rp)')
                                ->numeric()
                                ->default(0)
                                ->prefix('Rp')
                                ->helperText('Isi 0 jika tidak ada syarat minimum')
                                ->rules([
                                    fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $minCheckout   = (int) $value;
                                        $discountAmount = (int) $get('discount_amount');

                                        if ($minCheckout > 0 && $minCheckout <= $discountAmount) {
                                            $fail('Minimum checkout harus lebih besar dari jumlah potongan harga (Rp ' . number_format($discountAmount, 0, ',', '.') . ').');
                                        }
                                    },
                                ]),
                        ]),

                    // ── Field free_rental — hanya tampil saat type = free_rental ─
                    Select::make('barang_id')
                        ->label('Pilih Barang')
                        ->options(fn () => Barang::aktif()->pluck('nama', 'id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->visible(fn (Get $get) => $get('type') === 'free_rental')
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if (! $state) {
                                $set('barang_nama_snapshot', null);
                                $set('barang_foto_url_snapshot', null);
                                $set('title', null);
                                return;
                            }

                            $barang = Barang::with('fotoUtama')->find($state);
                            if (! $barang) return;

                            $set('barang_nama_snapshot', $barang->nama);
                            $set('title', 'Gratis Sewa ' . $barang->nama);

                            $foto = $barang->fotoUtama;
                            if ($foto) {
                                $set('barang_foto_url_snapshot', $foto->path_foto);
                            } else {
                                $set('barang_foto_url_snapshot', null);
                            }
                        })
                        ->helperText('Setelah memilih barang, nama dan foto akan otomatis tersimpan sebagai snapshot'),

                    TextInput::make('title')
                        ->label('Judul Hadiah (otomatis terisi)')
                        ->required()
                        ->helperText('Otomatis terisi setelah memilih barang, bisa diedit manual')
                        ->visible(fn (Get $get) => $get('type') === 'free_rental'),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->placeholder('Contoh: Dapatkan gratis sewa tenda untuk 1 hari!')
                        ->rows(2)
                        ->visible(fn (Get $get) => $get('type') === 'free_rental'),

                    Placeholder::make('snapshot_info')
                        ->label('📸 Data Snapshot (tersimpan permanen)')
                        ->content(function (Get $get) {
                            $nama = $get('barang_nama_snapshot');
                            $foto = $get('barang_foto_url_snapshot');
                            if (! $nama) return 'Belum ada data snapshot. Pilih barang terlebih dahulu.';
                            return "Nama: {$nama}" . ($foto ? " | Foto Path: {$foto}" : " | Foto: tidak ada");
                        })
                        ->visible(fn (Get $get) => $get('type') === 'free_rental'),

                    TextInput::make('barang_nama_snapshot')
                        ->label('Nama Barang Snapshot')
                        ->helperText('Otomatis terisi, tidak perlu diubah')
                        ->visible(fn (Get $get) => $get('type') === 'free_rental' && (bool) $get('barang_id')),

                    TextInput::make('barang_foto_url_snapshot')
                        ->label('URL Foto Snapshot')
                        ->helperText('Otomatis terisi, tidak perlu diubah')
                        ->visible(fn (Get $get) => $get('type') === 'free_rental' && (bool) $get('barang_id')),
                ]),

            // ── Pengaturan probabilitas & masa berlaku ────────────────────────
            Section::make('Pengaturan Probabilitas & Masa Berlaku')
                ->icon('heroicon-o-adjustments-horizontal')
                ->columns(3)
                ->schema([
                    TextInput::make('weight')
                        ->label('Bobot (Weight)')
                        ->numeric()
                        ->default(100)
                        ->required()
                        ->helperText('Common=100, Rare=40, Epic=10. Makin besar makin sering keluar.'),

                    TextInput::make('valid_days')
                        ->label('Masa Berlaku Voucher')
                        ->numeric()
                        ->default(7)
                        ->required()
                        ->suffix('hari')
                        ->helperText('Dihitung sejak user menerima hadiah'),

                    Toggle::make('is_active')
                        ->label('Aktif di Pool Mystery Box')
                        ->default(true)
                        ->helperText('Nonaktifkan untuk sementara tanpa menghapus item'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Nama Hadiah')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->description ?? ''),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'discount'    => '💰 Diskon',
                        'free_rental' => '🎒 Gratis Sewa',
                        default       => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'discount'    => 'info',
                        'free_rental' => 'success',
                        default       => 'gray',
                    }),

                TextColumn::make('rarity')
                    ->label('Rarity')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Epic'  => 'danger',
                        'Rare'  => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('discount_amount')
                    ->label('Potongan / Barang')
                    ->formatStateUsing(function ($record) {
                        if ($record->type === 'free_rental') {
                            return '🎒 ' . ($record->barang_nama_snapshot ?? $record->barang?->nama ?? '—');
                        }
                        return 'Rp ' . number_format($record->discount_amount, 0, ',', '.');
                    }),

                TextColumn::make('weight')
                    ->label('Weight')
                    ->sortable()
                    ->description(fn ($record) => 'Semakin tinggi → lebih sering keluar'),

                TextColumn::make('valid_days')
                    ->label('Masa Berlaku')
                    ->formatStateUsing(fn ($state) => $state . ' hari')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe Hadiah')
                    ->options([
                        'discount'    => 'Diskon',
                        'free_rental' => 'Gratis Sewa',
                    ]),
                SelectFilter::make('rarity')
                    ->label('Kelangkaan')
                    ->options([
                        'Common' => 'Common',
                        'Rare'   => 'Rare',
                        'Epic'   => 'Epic',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('weight', 'desc')
            ->emptyStateIcon('heroicon-o-gift')
            ->emptyStateHeading('Belum ada item mystery box')
            ->emptyStateDescription('Tambahkan item hadiah yang bisa keluar dari mystery box user.')
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Tambah Item Mystery Box Pertama'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListMysteryBoxItems::route('/'),
            'create' => CreateMysteryBoxItem::route('/create'),
            'edit'   => EditMysteryBoxItem::route('/{record}/edit'),
        ];
    }
}
