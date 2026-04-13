<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required(),

                TextInput::make('password')
                    ->password()
                    ->required(fn($context) => $context === 'create')
                    ->dehydrateStateUsing(fn($state) => bcrypt($state))
                    ->dehydrated(fn($state) => filled($state))
                    ->validationMessages([
                        'confirmed' => 'Konfirmasi password tidak sesuai.',
                    ])
                    ->confirmed(),

                TextInput::make('password_confirmation')
                    ->password()
                    ->required(fn($context) => $context === 'create')
                    ->label('Konfirmasi Password'),

                Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'super_admin' => 'Super Admin',
                    ])
                    ->required(),

                TextInput::make('phone'),

                Textarea::make('alamat'),
            ]);
    }
}
