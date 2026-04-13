<?php

namespace App\Filament\Resources\Transaksis;

use App\Filament\Resources\Transaksis\Pages;
use App\Filament\Resources\Transaksis\Pages\CreateTransaksi;
use App\Filament\Resources\Transaksis\Pages\EditTransaksi;
use App\Filament\Resources\Transaksis\Pages\ListTransaksis;
use App\Filament\Resources\Transaksis\Schemas\TransaksiForm;
use App\Filament\Resources\Transaksis\Tables\TransaksisTable;
use App\Models\Transaksi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nomor_transaksi';

    public static function form(Schema $schema): Schema
    {
        return TransaksiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransaksisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    // 🔥 FIX N+1 QUERY
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user',
                'detail.barang.foto',
                'jaminan',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransaksis::route('/'),
            'create' => CreateTransaksi::route('/create'),
            'edit' => EditTransaksi::route('/{record}/edit'),
            'view' => Pages\ViewTransaksi::route('/{record}'),
        ];
    }
}
