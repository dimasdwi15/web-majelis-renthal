<?php

namespace App\Filament\Resources\Rewards\Pages;

use App\Filament\Resources\Rewards\MysteryBoxItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMysteryBoxItem extends CreateRecord
{
    protected static string $resource = MysteryBoxItemResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
