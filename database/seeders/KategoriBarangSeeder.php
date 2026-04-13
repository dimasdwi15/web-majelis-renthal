<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KategoriBarang;

class KategoriBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = ['Tenda', 'Carrier', 'Sleeping Bag', 'Sepatu', 'Kompor'];

        foreach ($data as $item) {
            KategoriBarang::create([
                'nama' => $item,
                'slug' => strtolower(str_replace(' ', '-', $item)),
            ]);
        }
    }
}
