<?php

namespace App\Filament\Resources\Barangs\Pages;

use App\Filament\Resources\Barangs\BarangResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Barangs\Widgets\BarangStats;

class ListBarangs extends ListRecords
{
    protected static string $resource = BarangResource::class;

    public function getTitle(): string
    {
        return 'Manajemen Barang';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Barang')
                ->color('primary'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BarangStats::class,
        ];
    }

    public function getSubheading(): ?string
    {
        return 'Kelola inventaris peralatan outdoor dan pantau ketersediaan stok.';
    }
}
