<?php

namespace App\Filament\Resources\KategoriBarangs\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ViewColumn;


class KategoriBarangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->searchable(),

                ViewColumn::make('ikon')
                    ->label('Icon')
                    ->view('components.icon-renderer'),

                IconColumn::make('aktif')
                    ->boolean()
                    ->label('Aktif'),
            ])
            ->filters([
                //
            ])

            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit Kategori Barang')
                    ->modalSubmitActionLabel('Update'),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
