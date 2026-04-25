<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // ── Tag & Weather Rules (urutan PENTING — tag harus ada dulu) ──
            TagSeeder::class,           // 1. Buat semua tag fungsional
            WeatherTagRuleSeeder::class, // 2. Mapping cuaca → tag
        ]);
    }
}
