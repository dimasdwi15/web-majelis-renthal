<?php

namespace App\Filament\Resources\Barangs;

use App\Filament\Resources\Barangs\Pages\ListBarangs;
use App\Filament\Resources\Barangs\Schemas\BarangForm;
use App\Filament\Resources\Barangs\Tables\BarangsTable;
use App\Models\Barang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BarangResource extends Resource
{
    protected static ?string $model = Barang::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';
    protected static string|\UnitEnum|null $navigationGroup = 'Barang';
    protected static ?string $navigationLabel = 'Manajemen Barang';
    protected static ?string $recordTitleAttribute = 'nama';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return BarangForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BarangsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('fotoUtama');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBarangs::route('/'),
        ];
    }
}
