<?php

namespace App\Filament\Resources\BarangRusaks;

use App\Filament\Resources\BarangRusaks\Pages\ListBarangRusaks;
use App\Filament\Resources\BarangRusaks\Tables\BarangRusaksTable;
use App\Models\BarangRusak;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BarangRusakResource extends Resource
{
    protected static ?string $model = BarangRusak::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Barang';

    protected static ?string $navigationLabel = 'Barang Rusak';

    protected static ?string $modelLabel = 'Barang rusak';

    protected static ?string $pluralModelLabel = 'Barang rusak';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return BarangRusaksTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['barang.fotoUtama', 'transaksi.user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBarangRusaks::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
