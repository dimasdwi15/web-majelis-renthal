<!DOCTYPE html>
<html class="light" lang="id">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout | MAJELIS RENTAL</title>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800;900&family=Space+Grotesk:wght@700&display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap"
        rel="stylesheet" />

    {{--
        Midtrans Snap.js
        - Sandbox : https://app.sandbox.midtrans.com/snap/snap.js
        - Production: https://app.midtrans.com/snap/snap.js
        data-client-key wajib diisi dari config midtrans.
    --}}
    @if(config('midtrans.is_production'))
        <script src="https://app.midtrans.com/snap/snap.js"
                data-client-key="{{ config('midtrans.client_key') }}"></script>
    @else
        <script src="https://app.sandbox.midtrans.com/snap/snap.js"
                data-client-key="{{ config('midtrans.client_key') }}"></script>
    @endif
</head>

<body class="bg-[#f5f3ed] text-[#1b1c1a] antialiased">
    @include('user.components.navbar')

    <div x-data="checkout" x-init="init()" class="max-w-screen-xl mx-auto px-4 md:px-8 py-12">

        {{-- PAGE HEADER --}}
        <div class="mb-12">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-px w-10 bg-[#4d462e]"></div>
                <span class="font-syne text-[10px] font-bold tracking-[0.3em] uppercase text-[#7b6f52]">Majelis
                    Rental</span>
            </div>
            <h1 class="font-syne font-extrabold text-4xl md:text-5xl uppercase tracking-tight leading-none mb-4">
                Konfirmasi <span class="text-[#4d462e]">Sewa</span>
            </h1>
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-[10px] tracking-[0.18em] uppercase text-[#7b6f52]">Detail Pesanan</span>
                <span class="text-[#c8bfa8]">·</span>
                <span class="text-[10px] tracking-[0.18em] uppercase text-[#7b6f52]">Jaminan</span>
                <span class="text-[#c8bfa8]">·</span>
                <span class="text-[10px] tracking-[0.18em] uppercase text-[#7b6f52]">Pembayaran</span>
            </div>
        </div>

        {{-- Flash --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-5">
                <ul class="list-disc list-inside text-red-600 text-sm space-y-1">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-5 text-red-600 text-sm">
                {{ session('error') }}</div>
        @endif

        <div class="flex flex-col xl:flex-row gap-8 items-start">

            {{-- ═══ KOLOM KIRI ═══ --}}
            <div class="flex-1 space-y-5 min-w-0">

                {{-- 01 · DURASI SEWA --}}
                <div
                    class="bg-white border border-[#e0d9c8] rounded-2xl overflow-hidden shadow-sm transition-shadow duration-300 hover:shadow-md">
                    <div
                        class="flex items-center gap-3 px-6 py-4 bg-gradient-to-r from-[#faf8f2] to-[#f5f3ec] border-b border-[#e0d9c8]">
                        <span
                            class="font-syne w-8 h-8 rounded-xl bg-[#4d462e] text-[#F2E8C6] text-[11px] font-bold flex items-center justify-center flex-shrink-0">01</span>
                        <h2 class="font-syne font-bold text-[13px] tracking-[0.12em] uppercase">Durasi Sewa</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                            <div>
                                <label
                                    class="block text-[9px] font-semibold tracking-[0.15em] uppercase text-[#7b6f52] mb-2">Tanggal
                                    Ambil</label>
                                <div class="relative">
                                    <span
                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#7b6f52] text-[18px] pointer-events-none">calendar_today</span>
                                    <input type="date" x-model="tglAmbil" :min="today()"
                                        x-on:change="if(tglKembali && tglKembali <= tglAmbil) tglKembali = ''"
                                        class="w-full pl-10 pr-3 py-3 border-[1.5px] border-[#e0d9c8] rounded-xl text-sm bg-white focus:outline-none focus:border-[#4d462e] focus:ring-2 focus:ring-[#4d462e]/10 transition-all">
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-[9px] font-semibold tracking-[0.15em] uppercase text-[#7b6f52] mb-2">Tanggal
                                    Kembali</label>
                                <div class="relative">
                                    <span
                                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#7b6f52] text-[18px] pointer-events-none">event</span>
                                    <input type="date" x-model="tglKembali" :min="tglAmbil || today()"
                                        :disabled="!tglAmbil"
                                        class="w-full pl-10 pr-3 py-3 border-[1.5px] border-[#e0d9c8] rounded-xl text-sm bg-white focus:outline-none focus:border-[#4d462e] focus:ring-2 focus:ring-[#4d462e]/10 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                                </div>
                            </div>
                            <div
                                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#2e2a1e] to-[#4d462e] px-6 py-5 text-center">
                                <div class="absolute -top-4 -right-4 w-16 h-16 rounded-full bg-white/5"></div>
                                <span class="font-syne font-extrabold text-4xl text-[#F2E8C6] leading-none block"
                                    x-text="durasi || '—'"></span>
                                <span class="text-[9px] tracking-[0.22em] uppercase text-[#F2E8C6]/60 mt-1 block">Hari
                                    Sewa</span>
                            </div>
                        </div>
                        <div x-show="tglAmbil && tglKembali && durasi === 0" x-cloak
                            class="mt-4 flex items-center gap-2 text-red-600 text-xs bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                            <span class="material-symbols-outlined text-[16px]">error_outline</span>
                            Tanggal kembali harus setelah tanggal ambil.
                        </div>
                    </div>
                </div>

                {{-- 02 · INVENTARIS GEAR --}}
                <div
                    class="bg-white border border-[#e0d9c8] rounded-2xl overflow-hidden shadow-sm transition-shadow duration-300 hover:shadow-md">

                    <div
                        class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-[#faf8f2] to-[#f5f3ec] border-b border-[#e0d9c8]">
                        <div class="flex items-center gap-3">
                            <span
                                class="font-syne w-8 h-8 rounded-xl bg-[#4d462e] text-[#F2E8C6] text-[11px] font-bold flex items-center justify-center">02</span>
                            <h2 class="font-syne font-bold text-[13px] tracking-[0.12em] uppercase">Inventaris Gear</h2>
                        </div>
                        <a href="{{ route('katalog') }}"
                            class="flex items-center gap-1.5 text-[10px] font-bold tracking-[0.12em] uppercase text-[#4d462e] hover:text-[#2e2a1e]">
                            <span class="material-symbols-outlined text-[15px]">add_circle</span>Tambah Item
                        </a>
                    </div>

                    <div class="divide-y divide-[#f0ece0]">
                        <template x-if="$store.cart.isEmpty">
                            <div class="py-16 text-center">
                                <span
                                    class="material-symbols-outlined text-5xl text-[#c8bfa8] block mb-3">shopping_bag</span>
                                <p class="text-sm text-[#7b6f52] mb-5">Keranjang masih kosong</p>
                                <a href="{{ route('katalog') }}"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#4d462e] text-[#F2E8C6] rounded-xl text-[10px] font-syne font-bold uppercase">
                                    Ke Katalog →
                                </a>
                            </div>
                        </template>

                        <template x-for="(item, id) in $store.cart.items" :key="id">
                            <div class="flex items-start gap-4 px-6 py-4 hover:bg-[#faf8f2]">
                                <div
                                    class="w-[68px] h-[68px] rounded-xl overflow-hidden bg-[#f5f3ed] border border-[#e0d9c8] flex-shrink-0">
                                    <img :src="item.foto ? '/storage/' + item.foto : '/images/no-image.png'"
                                        :alt="item.nama" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-syne font-bold text-[14px] uppercase text-[#1b1c1a] leading-tight mb-0.5"
                                        x-text="item.nama"></p>
                                    <p class="text-[11px] text-[#7b6f52] mb-2">
                                        <span x-text="rupiah(item.harga)"></span>/hari
                                        <span class="text-[#c8bfa8] ml-2">·</span>
                                        <span class="text-[#9b947c] ml-1">Stok: <span x-text="item.stok"></span></span>
                                    </p>
                                    <div class="flex items-center gap-2 mb-2">
                                        <button @click="kurangQty(id)"
                                            class="w-7 h-7 rounded-lg border border-[#c8bfa8] hover:bg-[#f5f3ed] transition-colors flex items-center justify-center text-[#4a473d] font-bold">
                                            <span class="material-symbols-outlined text-[14px]">remove</span>
                                        </button>
                                        <span class="font-bold text-[13px] w-6 text-center" x-text="item.qty"></span>
                                        <button @click="tambahQty(id)"
                                            class="w-7 h-7 rounded-lg border border-[#c8bfa8] hover:bg-[#f5f3ed] transition-colors flex items-center justify-center text-[#4a473d] font-bold"
                                            :class="item.qty >= item.stok ? 'opacity-40 cursor-not-allowed' : ''">
                                            <span class="material-symbols-outlined text-[14px]">add</span>
                                        </button>
                                        <span x-show="item.qty >= item.stok"
                                            class="text-[9px] font-bold text-amber-600 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
                                            Maks
                                        </span>
                                    </div>
                                    <template x-if="durasi > 0">
                                        <p class="text-[12px] font-semibold text-[#4d462e]">
                                            Subtotal:
                                            <span x-text="rupiah(item.harga * item.qty * durasi)"></span>
                                        </p>
                                    </template>
                                    <template x-if="durasi === 0">
                                        <p class="text-[11px] text-[#9b947c]">Pilih durasi untuk lihat subtotal</p>
                                    </template>
                                </div>
                                <div class="flex flex-col items-end justify-between h-[68px]">
                                    <button @click="hapusItem(id)"
                                        class="text-[#9b947c] hover:text-red-600 transition-colors" title="Hapus item">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                    <p class="text-[12px] font-semibold text-[#4d462e]">
                                        <span x-text="rupiah(item.qty * item.harga)"></span>
                                        <span class="text-[#9b947c] font-normal">/hr</span>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <template x-if="!$store.cart.isEmpty">
                        <div
                            class="flex justify-between items-center px-6 py-3 bg-[#faf8f2] border-t border-[#e0d9c8]">
                            <span class="text-[10px] text-[#7b6f52] uppercase tracking-widest">Rate / Hari</span>
                            <span class="font-syne font-extrabold text-[16px] text-[#4d462e]"
                                x-text="rupiah(subtotalPerHari)">
                            </span>
                        </div>
                    </template>
                </div>

                {{-- 03 · IDENTITAS & JAMINAN --}}
                <div
                    class="bg-white border border-[#e0d9c8] rounded-2xl overflow-hidden shadow-sm transition-shadow duration-300 hover:shadow-md">
                    <div
                        class="flex items-center gap-3 px-6 py-4 bg-gradient-to-r from-[#faf8f2] to-[#f5f3ec] border-b border-[#e0d9c8]">
                        <span
                            class="font-syne w-8 h-8 rounded-xl bg-[#4d462e] text-[#F2E8C6] text-[11px] font-bold flex items-center justify-center flex-shrink-0">03</span>
                        <h2 class="font-syne font-bold text-[13px] tracking-[0.12em] uppercase">Identitas &amp; Jaminan
                        </h2>
                    </div>
                    <div class="p-6 space-y-5">
                        <div
                            class="flex items-start gap-3 p-4 bg-[#4d462e]/5 border border-[#4d462e]/15 rounded-xl text-xs text-[#5a584f] leading-relaxed">
                            <span
                                class="material-symbols-outlined text-[18px] text-[#4d462e] flex-shrink-0 mt-0.5">info</span>
                            <span>Kartu identitas fisik Anda akan <strong>ditahan admin</strong> saat pengambilan barang
                                sebagai jaminan, dan dikembalikan ketika barang kembali dalam kondisi baik.</span>
                        </div>

                        <div>
                            <label
                                class="block text-[9px] font-semibold tracking-[0.15em] uppercase text-[#7b6f52] mb-3">Jenis
                                Identitas</label>
                            <div class="grid grid-cols-3 gap-3">
                                @foreach ([['val' => 'KTP', 'icon' => 'badge', 'label' => 'KTP'], ['val' => 'SIM', 'icon' => 'directions_car', 'label' => 'SIM'], ['val' => 'PELAJAR', 'icon' => 'school', 'label' => 'Pelajar']] as $opt)
                                    <label class="cursor-pointer">
                                        <input type="radio" x-model="jenisIdentitas" value="{{ $opt['val'] }}"
                                            class="sr-only">
                                        <div class="flex flex-col items-center gap-2 p-4 border-2 rounded-xl transition-all duration-200 hover:-translate-y-0.5"
                                            :class="jenisIdentitas === '{{ $opt['val'] }}'
                                                ? 'border-[#4d462e] bg-[#4d462e]/5 shadow-[0_0_0_3px_rgba(77,70,46,0.08)]'
                                                : 'border-[#e0d9c8] hover:border-[#c8bfa8]'">
                                            <div class="w-11 h-11 rounded-xl flex items-center justify-center transition-colors"
                                                :class="jenisIdentitas === '{{ $opt['val'] }}' ? 'bg-[#4d462e]/10' : 'bg-[#f5f3ed]'">
                                                <span
                                                    class="material-symbols-outlined text-[20px] text-[#4d462e]">{{ $opt['icon'] }}</span>
                                            </div>
                                            <span
                                                class="font-syne font-bold text-[11px] tracking-[0.08em] uppercase text-[#1b1c1a]">{{ $opt['label'] }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-[9px] font-semibold tracking-[0.15em] uppercase text-[#7b6f52] mb-2">
                                Foto <span x-text="jenisIdentitas"></span>
                            </label>
                            <div x-on:click="document.getElementById('foto_identitas').click()"
                                class="cursor-pointer">
                                <div x-show="!fotoPreview"
                                    class="border-2 border-dashed border-[#c8bfa8] rounded-2xl p-10 text-center hover:border-[#4d462e] hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
                                    <span
                                        class="material-symbols-outlined text-[40px] text-[#c8bfa8] block mb-3">upload_file</span>
                                    <p class="text-[14px] font-semibold text-[#4a473d]">Klik untuk upload foto <span
                                            x-text="jenisIdentitas"></span></p>
                                    <p class="text-[11px] text-[#9b947c] mt-1">JPG, PNG, WEBP — maks. 5 MB</p>
                                </div>
                                <div x-show="fotoPreview"
                                    class="upload-preview-wrap relative rounded-2xl overflow-hidden border border-[#e0d9c8]">
                                    <img :src="fotoPreview" class="w-full max-h-52 object-cover block">
                                    <div
                                        class="upload-overlay absolute inset-0 bg-black/45 flex items-center justify-center opacity-0 transition-opacity duration-200">
                                        <span
                                            class="font-syne font-bold text-white text-[11px] tracking-[0.15em] uppercase">Ganti
                                            Foto</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 04 · METODE PEMBAYARAN --}}
                <div
                    class="bg-white border border-[#e0d9c8] rounded-2xl overflow-hidden shadow-sm transition-shadow duration-300 hover:shadow-md">
                    <div
                        class="flex items-center gap-3 px-6 py-4 bg-gradient-to-r from-[#faf8f2] to-[#f5f3ec] border-b border-[#e0d9c8]">
                        <span
                            class="font-syne w-8 h-8 rounded-xl bg-[#4d462e] text-[#F2E8C6] text-[11px] font-bold flex items-center justify-center flex-shrink-0">04</span>
                        <h2 class="font-syne font-bold text-[13px] tracking-[0.12em] uppercase">Metode Pembayaran</h2>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Midtrans --}}
                        <label class="cursor-pointer">
                            <input type="radio" x-model="metodePembayaran" value="midtrans" class="sr-only">
                            <div class="p-5 border-2 rounded-2xl transition-all duration-200 h-full hover:-translate-y-0.5"
                                :class="metodePembayaran === 'midtrans'
                                    ? 'border-[#4d462e] bg-[#4d462e]/[0.03] shadow-[0_0_0_3px_rgba(77,70,46,0.08)]'
                                    : 'border-[#e0d9c8] hover:border-[#c8bfa8]'">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-[#4d462e]/10 flex items-center justify-center">
                                            <span
                                                class="material-symbols-outlined text-[20px] text-[#4d462e]">credit_card</span>
                                        </div>
                                        <span
                                            class="font-syne font-extrabold text-[13px] uppercase text-[#1b1c1a]">Cashless</span>
                                    </div>
                                    <div class="w-[18px] h-[18px] rounded-full border-2 flex items-center justify-center flex-shrink-0 mt-0.5 transition-colors"
                                        :class="metodePembayaran === 'midtrans' ? 'border-[#4d462e]' : 'border-[#c8bfa8]'">
                                        <div x-show="metodePembayaran === 'midtrans'"
                                            class="w-2 h-2 rounded-full bg-[#4d462e]"></div>
                                    </div>
                                </div>
                                <p class="text-[11px] text-[#7b6f52] leading-relaxed mb-3">
                                    Transfer bank, QRIS, e-wallet via <strong class="text-[#4a473d]">Midtrans</strong>.
                                    Popup pembayaran muncul langsung setelah checkout.
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach (['BCA', 'BNI', 'BRI', 'QRIS', 'GoPay', 'OVO'] as $m)
                                        <span
                                            class="text-[9px] font-bold bg-[#4d462e]/8 text-[#4d462e] border border-[#4d462e]/15 px-2 py-0.5 rounded-md">{{ $m }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </label>

                        {{-- Tunai --}}
                        <label class="cursor-pointer">
                            <input type="radio" x-model="metodePembayaran" value="tunai" class="sr-only">
                            <div class="p-5 border-2 rounded-2xl transition-all duration-200 h-full hover:-translate-y-0.5"
                                :class="metodePembayaran === 'tunai'
                                    ? 'border-[#4d462e] bg-[#4d462e]/[0.03] shadow-[0_0_0_3px_rgba(77,70,46,0.08)]'
                                    : 'border-[#e0d9c8] hover:border-[#c8bfa8]'">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-[#4d462e]/10 flex items-center justify-center">
                                            <span
                                                class="material-symbols-outlined text-[20px] text-[#4d462e]">payments</span>
                                        </div>
                                        <span class="font-syne font-extrabold text-[13px] uppercase text-[#1b1c1a]">COD
                                            / Tunai</span>
                                    </div>
                                    <div class="w-[18px] h-[18px] rounded-full border-2 flex items-center justify-center flex-shrink-0 mt-0.5 transition-colors"
                                        :class="metodePembayaran === 'tunai' ? 'border-[#4d462e]' : 'border-[#c8bfa8]'">
                                        <div x-show="metodePembayaran === 'tunai'"
                                            class="w-2 h-2 rounded-full bg-[#4d462e]"></div>
                                    </div>
                                </div>
                                <p class="text-[11px] text-[#7b6f52] leading-relaxed mb-3">
                                    Bayar langsung di toko saat pengambilan. Stok berkurang setelah <strong
                                        class="text-[#4a473d]">admin konfirmasi</strong>.
                                </p>
                                <div
                                    class="flex items-start gap-2 text-[10px] text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
                                    <span
                                        class="material-symbols-outlined text-[14px] flex-shrink-0 mt-px">schedule</span>
                                    Auto-batal jika tidak dikonfirmasi admin hingga H+1 tanggal ambil.
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- 05 · E-KONTRAK --}}
                <div
                    class="bg-white border border-[#e0d9c8] rounded-2xl overflow-hidden shadow-sm transition-shadow duration-300 hover:shadow-md">
                    <div
                        class="flex items-center gap-3 px-6 py-4 bg-gradient-to-r from-[#faf8f2] to-[#f5f3ec] border-b border-[#e0d9c8]">
                        <span
                            class="font-syne w-8 h-8 rounded-xl bg-[#4d462e] text-[#F2E8C6] text-[11px] font-bold flex items-center justify-center flex-shrink-0">05</span>
                        <h2 class="font-syne font-bold text-[13px] tracking-[0.12em] uppercase">Perjanjian E-Kontrak
                        </h2>
                    </div>
                    <div class="p-6">
                        <div
                            class="h-52 overflow-y-auto scrollbar-hide bg-[#faf8f2] border border-[#e0d9c8] rounded-xl p-5 text-[12px] text-[#5a584f] leading-relaxed space-y-3 mb-5">
                            <p class="font-syne font-bold text-[11px] tracking-[0.1em] uppercase text-[#1b1c1a]">Syarat
                                dan Ketentuan Penyewaan</p>
                            <p><strong class="text-[#1b1c1a]">1. KEWAJIBAN PENYEWA</strong><br>
                                Penyewa wajib menjaga barang sewaan dalam kondisi baik dan mengembalikan tepat waktu.
                                Kerusakan akibat kelalaian penyewa menjadi tanggung jawab penyewa sepenuhnya.</p>
                            <p><strong class="text-[#1b1c1a]">2. JAMINAN IDENTITAS</strong><br>
                                Kartu identitas fisik penyewa akan ditahan selama masa sewa. Dikembalikan setelah semua
                                barang kembali dalam kondisi baik.</p>
                            <p><strong class="text-[#1b1c1a]">3. KETERLAMBATAN PENGEMBALIAN</strong><br>
                                Keterlambatan dikenakan denda sebesar harga sewa per hari, dihitung mulai H+1 tanggal
                                kembali.</p>
                            <p><strong class="text-[#1b1c1a]">4. KERUSAKAN BARANG</strong><br>
                                Kerusakan dinilai admin saat pengembalian. Penyewa wajib membayar biaya perbaikan atau
                                penggantian.</p>
                            <p><strong class="text-[#1b1c1a]">5. PEMBATALAN</strong><br>
                                Midtrans: pembatalan setelah lunas dikenakan biaya admin 10%. Tunai: dapat dibatalkan
                                sebelum konfirmasi admin tanpa biaya.</p>
                            <p><strong class="text-[#1b1c1a]">6. FORCE MAJEURE</strong><br>
                                Majelis Rental tidak bertanggung jawab atas gangguan layanan akibat bencana alam atau
                                kejadian di luar kendali manajemen.</p>
                        </div>

                        <label class="flex items-start gap-3 cursor-pointer select-none"
                            x-on:click="setuju = !setuju">
                            <div class="flex-shrink-0 mt-0.5 w-[22px] h-[22px] border-2 rounded-lg flex items-center justify-center transition-all duration-200"
                                :class="setuju ? 'bg-[#4d462e] border-[#4d462e]' : 'border-[#c8bfa8] hover:border-[#4d462e]/60'">
                                <span x-show="setuju"
                                    class="material-symbols-outlined text-[#F2E8C6] text-[14px]">check</span>
                            </div>
                            <p class="text-[13px] text-[#4a473d] leading-relaxed">
                                Saya telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan penyewaan
                                Majelis Rental. Saya bertanggung jawab penuh atas barang yang disewa.
                            </p>
                        </label>
                    </div>
                </div>
            </div>{{-- /kolom kiri --}}

            {{-- ═══ SIDEBAR KANAN ═══ --}}
            <div class="xl:w-[320px] flex-shrink-0 w-full">
                <div class="sticky top-20 flex flex-col gap-4">

                    {{-- Rincian biaya --}}
                    <div class="bg-white border border-[#e0d9c8] rounded-2xl overflow-hidden shadow-md">
                        <div class="bg-gradient-to-br from-[#2e2a1e] to-[#4d462e] px-6 py-5">
                            <p class="text-[9px] tracking-[0.22em] uppercase text-[#F2E8C6]/60 mb-1">Rincian Biaya</p>
                            <p class="font-syne font-extrabold text-2xl text-[#F2E8C6]"
                                x-text="durasi > 0 && !$store.cart.isEmpty ? rupiah(totalSewa) : 'Rp —'"></p>
                            <p class="text-[10px] text-[#F2E8C6]/50 mt-1"
                                x-text="durasi > 0 ? durasi + ' hari sewa' : 'Pilih durasi terlebih dahulu'"></p>
                        </div>
                        <div class="p-5">
                            <div class="space-y-2.5 mb-4">
                                <template x-for="(item, id) in $store.cart.items" :key="id">
                                    <div
                                        class="flex justify-between items-start gap-3 pb-2.5 border-b border-[#f0ece0]">
                                        <div class="min-w-0">
                                            <p class="font-syne font-bold text-[11px] uppercase tracking-tight text-[#1b1c1a] truncate"
                                                x-text="item.nama"></p>
                                            <p class="text-[10px] text-[#9b947c]">
                                                <span x-text="item.qty"></span> unit × <span
                                                    x-text="durasi || '?'"></span> hari
                                            </p>
                                        </div>
                                        <span class="font-semibold text-[12px] text-[#4d462e] flex-shrink-0"
                                            x-text="durasi > 0 ? rupiah(item.harga * item.qty * durasi) : '—'"></span>
                                    </div>
                                </template>
                                <template x-if="$store.cart.isEmpty">
                                    <p class="text-[11px] text-[#9b947c] text-center py-2">Keranjang masih kosong</p>
                                </template>
                            </div>

                            <div class="space-y-1.5 text-[12px] mb-4">
                                <div class="flex justify-between">
                                    <span class="text-[#7b6f52]">Biaya Admin</span>
                                    <span class="text-green-600 font-semibold">Gratis</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[#7b6f52]">Deposit Jaminan</span>
                                    <span class="text-[#4a473d] font-semibold">Identitas Fisik</span>
                                </div>
                            </div>

                            <div class="flex justify-between items-center text-[12px] mb-2">
                                <span class="text-[#7b6f52]">Rate / Hari</span>
                                <span class="font-semibold text-[#4a473d]" x-text="rupiah(subtotalPerHari)"></span>
                            </div>

                            <div class="h-px bg-[#e0d9c8] my-3"></div>

                            <div class="flex items-baseline justify-between mb-1">
                                <span
                                    class="text-[10px] font-bold tracking-[0.15em] uppercase text-[#7b6f52]">Total</span>
                                <span class="font-syne font-extrabold text-[26px] text-[#4d462e] leading-none"
                                    x-text="durasi > 0 && !$store.cart.isEmpty ? rupiah(totalSewa) : 'Rp —'"></span>
                            </div>
                            <p class="text-[9px] text-[#9b947c] tracking-[0.1em] uppercase mb-4">*Belum termasuk denda
                                keterlambatan</p>

                            <div
                                class="flex items-center gap-2.5 bg-[#faf8f2] border border-[#e0d9c8] rounded-xl px-4 py-3">
                                <span class="material-symbols-outlined text-[16px] text-[#4d462e]"
                                    x-text="metodePembayaran === 'midtrans' ? 'credit_card' : 'payments'"></span>
                                <span class="text-[10px] font-bold tracking-[0.1em] uppercase text-[#4a473d]"
                                    x-text="metodePembayaran === 'midtrans' ? 'Cashless via Midtrans' : 'COD / Tunai di Toko'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Form hidden — diisi via syncHidden() sebelum submit --}}
                    <form id="form-checkout" action="{{ route('checkout.proses') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="input_tgl_ambil" name="tanggal_ambil">
                        <input type="hidden" id="input_tgl_kembali" name="tanggal_kembali">
                        <input type="hidden" id="input_metode" name="metode_pembayaran">
                        <input type="hidden" id="input_jenis_id" name="jenis_identitas">
                        <input type="hidden" id="input_durasi" name="durasi">
                        <input type="file" id="foto_identitas" name="foto_identitas" accept="image/*"
                            class="sr-only" x-on:change="handleFoto($event)">
                    </form>

                    {{-- Error dari server (AJAX) --}}
                    <div x-show="submitError" x-cloak
                        class="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-2xl px-5 py-4">
                        <span class="material-symbols-outlined text-red-500 text-[18px] flex-shrink-0 mt-0.5">error</span>
                        <p class="text-[12px] text-red-600 leading-relaxed" x-text="submitError"></p>
                    </div>

                    {{-- ══ TOMBOL SUBMIT ══
                        - Untuk COD      : submit form biasa (sinkron)
                        - Untuk Midtrans : AJAX fetch → snap_token → window.snap.pay()
                    --}}
                    <button type="button" x-on:click="submitForm()"
                        :disabled="!bisaSubmit || loading"
                        class="w-full py-4 rounded-2xl font-syne font-extrabold text-[12px] tracking-[0.15em] uppercase border-0 transition-all duration-300"
                        :class="bisaSubmit && !loading
                            ? 'bg-gradient-to-r from-[#2e2a1e] to-[#4d462e] text-[#F2E8C6] hover:-translate-y-0.5 hover:shadow-xl hover:shadow-[#4d462e]/30 cursor-pointer'
                            : 'bg-[#e0d9c8] text-[#9b947c] cursor-not-allowed'">

                        {{-- State: Loading --}}
                        <span x-show="loading" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Memproses...
                        </span>

                        {{-- State: Normal --}}
                        <span x-show="!loading"
                            x-text="metodePembayaran === 'midtrans' ? 'Lanjut ke Pembayaran →' : 'Konfirmasi Pesanan →'">
                        </span>
                    </button>

                    {{-- Checklist persyaratan --}}
                    <div class="bg-white border border-[#e0d9c8] rounded-2xl p-5">
                        <p class="text-[9px] font-bold tracking-[0.2em] uppercase text-[#9b947c] mb-4">Persyaratan
                            Checkout</p>
                        <div class="space-y-2.5">
                            <template
                                x-for="item in [
                                    { label: 'Durasi sewa dipilih',     done: durasi > 0 },
                                    { label: 'Keranjang tidak kosong',  done: $store.cart.count > 0 },
                                    { label: 'Foto identitas diupload', done: fotoFile !== null },
                                    { label: 'Perjanjian disetujui',    done: setuju },
                                ]"
                                :key="item.label">
                                <div class="flex items-center gap-2.5 text-[11px] font-medium transition-colors duration-200"
                                    :class="item.done ? 'text-green-700' : 'text-[#9b947c]'">
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 transition-all duration-200"
                                        :class="item.done ? 'bg-green-100' : 'bg-[#f5f3ed]'">
                                        <span class="material-symbols-outlined text-[12px]"
                                            x-text="item.done ? 'check' : 'radio_button_unchecked'"></span>
                                    </div>
                                    <span x-text="item.label"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <a href="{{ route('keranjang.index') }}"
                        class="block text-center text-[10px] text-[#9b947c] tracking-[0.12em] uppercase py-2 hover:text-[#4d462e] transition-colors">
                        ← Kembali ke Keranjang
                    </a>

                </div>
            </div>

        </div>
    </div>

    @include('user.components.footer')
</body>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('checkout', () => ({

            // ── State ────────────────────────────────────────────────────
            tglAmbil: '',
            tglKembali: '',
            jenisIdentitas: 'KTP',
            fotoPreview: null,
            fotoFile: null,
            metodePembayaran: 'midtrans',
            setuju: false,

            // State untuk AJAX Midtrans
            loading: false,
            submitError: null,

            // ── Init ─────────────────────────────────────────────────────
            async init() {
                await Alpine.store('cart').refresh();
            },

            // ── Computed ─────────────────────────────────────────────────
            get subtotalPerHari() {
                return Object.values(Alpine.store('cart').items)
                    .reduce((s, i) => s + i.harga * i.qty, 0);
            },

            get durasi() {
                if (!this.tglAmbil || !this.tglKembali) return 0;
                const d = Math.round(
                    (new Date(this.tglKembali) - new Date(this.tglAmbil)) / 86400000
                );
                return d > 0 ? d : 0;
            },

            get totalSewa() {
                return this.subtotalPerHari * (this.durasi || 0);
            },

            get bisaSubmit() {
                return this.durasi > 0 &&
                    Alpine.store('cart').count > 0 &&
                    this.jenisIdentitas !== '' &&
                    this.fotoFile !== null &&
                    this.setuju;
            },

            // ── Mutasi Cart ──────────────────────────────────────────────
            hapusItem(id) {
                Alpine.store('cart').hapus(id);
                this.autoRefresh();
            },

            tambahQty(id) {
                const item = Alpine.store('cart').items[id];
                if (!item) return;
                if (item.qty < item.stok) {
                    Alpine.store('cart').update(id, item.qty + 1);
                    this.autoRefresh();
                } else {
                    Alpine.store('toast').flash(
                        `Stok "${item.nama}" sudah maksimal (${item.stok} unit).`, 'error'
                    );
                }
            },

            kurangQty(id) {
                const item = Alpine.store('cart').items[id];
                if (!item) return;
                if (item.qty > 1) {
                    Alpine.store('cart').update(id, item.qty - 1);
                    this.autoRefresh();
                } else {
                    Alpine.store('cart').hapus(id);
                    this.autoRefresh();
                }
            },

            autoRefresh() {
                setTimeout(() => window.location.reload(), 600);
            },

            // ── Foto Identitas ───────────────────────────────────────────
            handleFoto(e) {
                const file = e.target.files[0];
                if (!file) return;
                this.fotoFile = file;
                const r = new FileReader();
                r.onload = ev => { this.fotoPreview = ev.target.result; };
                r.readAsDataURL(file);
            },

            // ── Helpers ──────────────────────────────────────────────────
            rupiah(n) {
                return 'Rp\u00a0' + new Intl.NumberFormat('id-ID').format(n);
            },

            today() {
                return new Date().toISOString().split('T')[0];
            },

            syncHidden() {
                document.getElementById('input_tgl_ambil').value  = this.tglAmbil;
                document.getElementById('input_tgl_kembali').value = this.tglKembali;
                document.getElementById('input_metode').value     = this.metodePembayaran;
                document.getElementById('input_jenis_id').value   = this.jenisIdentitas;
                document.getElementById('input_durasi').value     = this.durasi;
            },

            // ── Submit Utama ─────────────────────────────────────────────
            /**
             * Untuk COD  : submit form biasa (sinkron).
             * Untuk Midtrans : AJAX → dapatkan snap_token → buka popup Snap.
             *
             * Alur Midtrans:
             *   1. syncHidden()    → isi hidden input
             *   2. FormData(form)  → kumpulkan semua field + file foto
             *   3. fetch POST      → CheckoutController::proses() → JSON { snap_token, redirect_url }
             *   4. snap.pay()      → popup Midtrans muncul
             *   5. onSuccess/onPending/onClose → redirect ke halaman struk
             */
            async submitForm() {
                if (!this.bisaSubmit || this.loading) return;

                this.submitError = null;
                this.syncHidden();

                // ── COD: submit biasa ──────────────────────────────────
                if (this.metodePembayaran !== 'midtrans') {
                    document.getElementById('form-checkout').submit();
                    return;
                }

                // ── MIDTRANS: AJAX + Snap popup ────────────────────────
                this.loading = true;

                try {
                    const form     = document.getElementById('form-checkout');
                    const formData = new FormData(form);

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            // _token sudah ada di FormData via @csrf, tapi
                            // tambahkan header untuk keamanan ekstra
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    // Validasi / error server
                    if (!response.ok) {
                        const errorMessages = data.errors
                            ? Object.values(data.errors).flat().join(' • ')
                            : (data.message || 'Terjadi kesalahan. Silakan coba lagi.');
                        this.submitError = errorMessages;
                        this.loading = false;
                        return;
                    }

                    // ── Buka Midtrans Snap popup ───────────────────────
                    window.snap.pay(data.snap_token, {

                        // Pembayaran berhasil → redirect ke struk
                        onSuccess: (_result) => {
                            this.loading = false;
                            window.location.href = data.redirect_url;
                        },

                        // Pembayaran pending (misal: VA belum dibayar)
                        // Redirect ke struk, status masih "menunggu_pembayaran"
                        // dan akan di-update oleh webhook Midtrans
                        onPending: (_result) => {
                            this.loading = false;
                            window.location.href = data.redirect_url;
                        },

                        // Pembayaran gagal → tampilkan error, biarkan user coba lagi
                        onError: (_result) => {
                            this.loading = false;
                            this.submitError =
                                'Pembayaran gagal. Silakan coba metode pembayaran lain atau ulangi.';
                        },

                        // User menutup popup tanpa bayar → redirect ke struk
                        // (pesanan sudah dibuat dengan status "menunggu_pembayaran",
                        //  akan dibatalkan otomatis oleh scheduler setelah 24 jam)
                        onClose: () => {
                            this.loading = false;
                            window.location.href = data.redirect_url;
                        },
                    });

                } catch (err) {
                    this.loading = false;
                    this.submitError = 'Terjadi kesalahan jaringan. Periksa koneksi Anda dan coba lagi.';
                    console.error('[Checkout Midtrans]', err);
                }
            },
        }));
    });
</script>

</html>
