<?php

namespace App\Filament\Resources\BarangRusaks\Pages;

use App\Filament\Resources\BarangRusaks\BarangRusakResource;
use Filament\Resources\Pages\ListRecords;

class ListBarangRusaks extends ListRecords
{
    protected static string $resource = BarangRusakResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
