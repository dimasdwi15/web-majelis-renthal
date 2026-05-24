<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\VoucherTemplate;
use Illuminate\Database\Seeder;

class VoucherTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────────────────────────
        // 1. VOUCHER DISKON (Tukar XP → Potongan harga)
        // ─────────────────────────────────────────────────────────────────
        $discountVouchers = [
            [
                'code'            => 'HEMAT15',
                'title'           => 'Potongan Rp15.000',
                'description'     => 'Potongan langsung Rp15.000 untuk sewa minimal Rp100.000',
                'type'            => 'discount',
                'rarity'          => 'Common',
                'discount_amount' => 15000,
                'min_checkout'    => 100000,
                'free_barang_id'  => null,
                'xp_cost'         => 100,
                'valid_days'      => 3,
                'is_active'       => true,
                'is_mystery_pool' => false,
            ],
            [
                'code'            => 'HEMAT40',
                'title'           => 'Potongan Rp40.000',
                'description'     => 'Potongan langsung Rp40.000 untuk sewa minimal Rp200.000',
                'type'            => 'discount',
                'rarity'          => 'Rare',
                'discount_amount' => 40000,
                'min_checkout'    => 200000,
                'free_barang_id'  => null,
                'xp_cost'         => 200,
                'valid_days'      => 5,
                'is_active'       => true,
                'is_mystery_pool' => false,
            ],
            [
                'code'            => 'HEMAT100',
                'title'           => 'Potongan Rp100.000',
                'description'     => 'Potongan langsung Rp100.000 untuk sewa minimal Rp500.000',
                'type'            => 'discount',
                'rarity'          => 'Epic',
                'discount_amount' => 100000,
                'min_checkout'    => 500000,
                'free_barang_id'  => null,
                'xp_cost'         => 350,
                'valid_days'      => 7,
                'is_active'       => true,
                'is_mystery_pool' => false,
            ],
        ];

        foreach ($discountVouchers as $data) {
            VoucherTemplate::updateOrCreate(['code' => $data['code']], $data);
        }

        // ─────────────────────────────────────────────────────────────────
        // 2. MYSTERY BOX ITEMS (Free item — hanya dari mystery box)
        //    Barang murah & jarang disewa: Gaiter, Flysheet, Rain Cover, Headlamp
        // ─────────────────────────────────────────────────────────────────
        $mysteryItems = [
            ['nama' => 'Gaiter',         'code' => 'FREE_GAITER'],
            ['nama' => 'Flysheet',       'code' => 'FREE_FLYSHEET'],
            ['nama' => 'Rain Cover',     'code' => 'FREE_RAINCOVER'],
            ['nama' => 'Headlamp',       'code' => 'FREE_HEADLAMP'],
        ];

        foreach ($mysteryItems as $item) {
            // Cari barang di database berdasarkan nama (partial match)
            $barang = Barang::where('nama', 'like', '%' . $item['nama'] . '%')
                ->first();

            if ($barang) {
                VoucherTemplate::updateOrCreate(
                    ['code' => $item['code']],
                    [
                        'title'           => 'Gratis Sewa ' . $barang->nama,
                        'description'     => "Sewa {$barang->nama} GRATIS dari Mystery Box! Berlaku 7 hari.",
                        'type'            => 'free_item',
                        'rarity'          => 'Epic',
                        'discount_amount' => 0,
                        'min_checkout'    => 0,
                        'free_barang_id'  => $barang->id,
                        'xp_cost'         => null, // tidak bisa dibeli, hanya dari mystery box
                        'valid_days'      => 7,
                        'is_active'       => true,
                        'is_mystery_pool' => true,
                    ]
                );
            }
        }

        // Jika tidak ada barang yang match, buat template generic
        $hasPool = VoucherTemplate::where('is_mystery_pool', true)->exists();
        if (! $hasPool) {
            // Ambil 4 barang paling murah sebagai fallback
            $cheapItems = Barang::orderBy('harga_per_hari', 'asc')->limit(4)->get();
            foreach ($cheapItems as $index => $barang) {
                VoucherTemplate::updateOrCreate(
                    ['code' => 'FREE_ITEM_' . ($index + 1)],
                    [
                        'title'           => 'Gratis Sewa ' . $barang->nama,
                        'description'     => "Sewa {$barang->nama} GRATIS dari Mystery Box! Berlaku 7 hari.",
                        'type'            => 'free_item',
                        'rarity'          => $index < 2 ? 'Rare' : 'Epic',
                        'discount_amount' => 0,
                        'min_checkout'    => 0,
                        'free_barang_id'  => $barang->id,
                        'xp_cost'         => null,
                        'valid_days'      => 7,
                        'is_active'       => true,
                        'is_mystery_pool' => true,
                    ]
                );
            }
        }

        $this->command->info('✅ VoucherTemplateSeeder selesai: ' . VoucherTemplate::count() . ' template.');
    }
}
