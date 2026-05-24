<?php

namespace App\Filament\Resources\Rewards\Pages;

use App\Filament\Resources\Rewards\MysteryBoxItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMysteryBoxItems extends ListRecords
{
    protected static string $resource = MysteryBoxItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Item Mystery Box'),
        ];
    }
}
