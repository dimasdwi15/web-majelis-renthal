<?php

namespace App\Filament\Resources\KategoriBarangs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Illuminate\Support\HtmlString;

class KategoriBarangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            TextInput::make('nama')
                ->required()
                ->maxLength(255)
                ->live(debounce: 500)
                ->afterStateUpdated(
                    fn($state, callable $set) =>
                    $set('slug', Str::slug($state))
                ),

            TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true),

            Select::make('ikon')
                ->label('Icon')
                ->options([
                    'tent' => 'Camping Gear',
                    'backpack' => "Hiking Gear",
                    'shoe' => 'Apparel',
                    'bed-flat' => 'Accessories',
                    'stove' => 'Cooking Equipment',

                ])
                ->live()
                ->required(),

            ViewField::make('preview_icon')
                ->view('components.icon-preview')
                ->columnSpanFull(),

            Toggle::make('aktif')
                ->default(true),
        ]);
    }
}
