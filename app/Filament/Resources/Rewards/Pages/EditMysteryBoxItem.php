<?php

namespace App\Filament\Resources\Rewards\Pages;

use App\Filament\Resources\Rewards\MysteryBoxItemResource;
use Filament\Resources\Pages\EditRecord;

class EditMysteryBoxItem extends EditRecord
{
    protected static string $resource = MysteryBoxItemResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
