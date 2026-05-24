<?php

namespace App\Filament\Resources\Rewards\Pages;

use App\Filament\Resources\Rewards\VoucherResource;
use Filament\Resources\Pages\EditRecord;

class EditVoucher extends EditRecord
{
    protected static string $resource = VoucherResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
