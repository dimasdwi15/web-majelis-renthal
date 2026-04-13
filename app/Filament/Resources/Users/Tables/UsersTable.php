<?php

namespace App\Filament\Resources\Users\Tables;

use Dom\Text;
use Filament\Actions\DeleteBulkAction;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;


class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->modifyQueryUsing(function (Builder $query) {
                $query->whereIn('role', ['admin', 'super_admin']);
            })

            ->columns([

                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('role'),

                TextColumn::make('phone'),
            ])

            ->filters([
                SelectFilter::make('role')
                    ->label('Filter Role')
                    ->options([
                        'admin' => 'Admin',
                        'super_admin' => 'Super Admin',
                    ]),
            ])

            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit Admin')
                    ->modalWidth('lg'),

                DeleteAction::make(),
            ])

            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
