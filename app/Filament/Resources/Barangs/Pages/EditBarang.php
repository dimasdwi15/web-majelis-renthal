<?php

namespace App\Filament\Resources\Barangs\Pages;

use App\Filament\Resources\Barangs\BarangResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Models\BarangFoto;

class EditBarang extends EditRecord
{
    protected static string $resource = BarangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if (array_key_exists('foto', $this->data)) {

            if (!empty($this->data['foto'])) {

                $this->record->foto()->delete();

                foreach ($this->data['foto'] as $path) {
                    BarangFoto::create([
                        'barang_id' => $this->record->id,
                        'path_foto' => $path,
                    ]);
                }
            }
        }
    }
}
