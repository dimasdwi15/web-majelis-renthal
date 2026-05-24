<?php

namespace App\Filament\Resources\Rewards\Pages;

use App\Filament\Resources\Rewards\VoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Voucher Baru'),
        ];
    }
}
