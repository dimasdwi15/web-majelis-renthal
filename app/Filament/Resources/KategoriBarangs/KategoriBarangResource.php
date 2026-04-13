<?php

namespace App\Filament\Resources\KategoriBarangs;

use App\Filament\Resources\KategoriBarangs\Pages\ListKategoriBarangs;
use App\Filament\Resources\KategoriBarangs\Schemas\KategoriBarangForm;
use App\Filament\Resources\KategoriBarangs\Tables\KategoriBarangsTable;
use App\Models\KategoriBarang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KategoriBarangResource extends Resource
{
    protected static ?string $model = KategoriBarang::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'Barang';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return KategoriBarangForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KategoriBarangsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKategoriBarangs::route('/'),
        ];
    }
}
