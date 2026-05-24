<?php

namespace App\Filament\Resources\Rewards;

use App\Models\VoucherTemplate;
use App\Filament\Resources\Rewards\Pages\ListVouchers;
use App\Filament\Resources\Rewards\Pages\CreateVoucher;
use App\Filament\Resources\Rewards\Pages\EditVoucher;
use BackedEnum;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class VoucherResource extends Resource
{
    protected static ?string $model = VoucherTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';
    protected static string|\UnitEnum|null  $navigationGroup = 'Rewards';
    protected static ?string $navigationLabel = 'Kelola Voucher';
    protected static ?string $modelLabel      = 'Voucher';
    protected static ?string $pluralModelLabel = 'Voucher';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Voucher')
                ->description('Voucher yang bisa ditukar oleh user menggunakan XP')
                ->icon('heroicon-o-ticket')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Kode Voucher')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('Contoh: DISKON20')
                        ->helperText('Kode unik, huruf kapital, tanpa spasi')
                        ->maxLength(50),

                    TextInput::make('title')
                        ->label('Judul Voucher')
                        ->required()
                        ->placeholder('Contoh: Diskon 20%')
                        ->maxLength(100)
                        ->columnSpan(1),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->placeholder('Contoh: Potongan 20% untuk sewa minimal Rp200.000')
                        ->rows(2)
                        ->columnSpanFull(),

                    Select::make('type')
                        ->label('Tipe')
                        ->options([
                            'discount'  => '💰 Diskon (potongan harga)',
                            'free_item' => '🎒 Item Gratis (barang tertentu)',
                        ])
                        ->default('discount')
                        ->required()
                        ->live(),

                    Select::make('rarity')
                        ->label('Kelangkaan')
                        ->options([
                            'Common' => '⬜ Common',
                            'Rare'   => '🔵 Rare',
                            'Epic'   => '🟣 Epic',
                        ])
                        ->default('Common')
                        ->required(),
                ]),

            Section::make('Detail Diskon')
                ->description('Diisi jika tipe voucher adalah Diskon')
                ->icon('heroicon-o-banknotes')
                ->columns(2)
                ->schema([
                    TextInput::make('discount_amount')
                        ->label('Jumlah Potongan (Rp)')
                        ->numeric()
                        ->default(0)
                        ->prefix('Rp')
                        ->live(onBlur: true)
                        ->helperText('Isi 0 jika tidak ada potongan harga'),

                    TextInput::make('min_checkout')
                        ->label('Minimum Checkout (Rp)')
                        ->numeric()
                        ->default(0)
                        ->prefix('Rp')
                        ->helperText('Isi 0 jika tidak ada minimum. Jika diisi, harus lebih besar dari jumlah potongan.')
                        ->rules([
                            fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                $minCheckout    = (int) $value;
                                $discountAmount = (int) $get('discount_amount');

                                if ($minCheckout > 0 && $minCheckout <= $discountAmount) {
                                    $fail('Minimum checkout harus lebih besar dari jumlah potongan harga (Rp ' . number_format($discountAmount, 0, ',', '.') . ').');
                                }
                            },
                        ]),
                ]),

            Section::make('Pengaturan XP & Masa Berlaku')
                ->icon('heroicon-o-star')
                ->columns(2)
                ->schema([
                    TextInput::make('xp_cost')
                        ->label('Biaya XP untuk Redeem')
                        ->numeric()
                        ->placeholder('Contoh: 100')
                        ->helperText('Kosongkan jika voucher tidak bisa ditukar dengan XP')
                        ->suffix('XP'),

                    TextInput::make('valid_days')
                        ->label('Masa Berlaku Voucher')
                        ->numeric()
                        ->default(7)
                        ->required()
                        ->suffix('hari')
                        ->helperText('Dihitung sejak user menerima voucher'),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Nonaktifkan untuk menyembunyikan dari katalog redeem'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'discount'  => '💰 Diskon',
                        'free_item' => '🎒 Item Gratis',
                        default     => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'discount'  => 'info',
                        'free_item' => 'success',
                        default     => 'gray',
                    }),

                TextColumn::make('rarity')
                    ->label('Rarity')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Epic'   => 'danger',
                        'Rare'   => 'warning',
                        default  => 'gray',
                    }),

                TextColumn::make('discount_amount')
                    ->label('Potongan')
                    ->formatStateUsing(fn ($state) => $state > 0
                        ? 'Rp ' . number_format($state, 0, ',', '.')
                        : '-')
                    ->sortable(),

                TextColumn::make('xp_cost')
                    ->label('Biaya XP')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' XP' : '—')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'discount'  => 'Diskon',
                        'free_item' => 'Item Gratis',
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
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('xp_cost', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListVouchers::route('/'),
            'create' => CreateVoucher::route('/create'),
            'edit'   => EditVoucher::route('/{record}/edit'),
        ];
    }
}
