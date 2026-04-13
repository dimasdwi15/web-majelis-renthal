<?php

namespace App\Filament\Resources\Barangs\Pages;

use App\Filament\Resources\Barangs\BarangResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\BarangFoto;

class CreateBarang extends CreateRecord
{
    protected static string $resource = BarangResource::class;

    protected function afterCreate(): void
    {
        if ($this->data['foto'] ?? false) {
            foreach ($this->data['foto'] as $path) {
                BarangFoto::create([
                    'barang_id' => $this->record->id,
                    'path_foto' => $path,
                ]);
            }
        }
    }
}
