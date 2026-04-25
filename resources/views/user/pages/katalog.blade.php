<!DOCTYPE html>
<html class="light" lang="id">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Katalog Inventaris | MAJELIS RENTAL</title>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800;900&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet" />
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* ── Scrollbar ── */
        .modal-scroll::-webkit-scrollbar { width: 4px; }
        .modal-scroll::-webkit-scrollbar-track { background: transparent; }
        .modal-scroll::-webkit-scrollbar-thumb { background: #d6cfbe; border-radius: 4px; }
        .modal-scroll::-webkit-scrollbar-thumb:hover { background: #4d462e; }

        /* ── Spec list ── */
        .spec-line {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 7px 0;
            border-bottom: 1px dashed #e0d9c8;
            font-size: 12px;
            color: #5a584f;
            line-height: 1.5;
        }
        .spec-line:last-child { border-bottom: none; }
        .spec-bullet {
            width: 5px; height: 5px;
            border-radius: 50%;
            background: #4d462e;
            flex-shrink: 0;
            margin-top: 6px;
        }

        /* ── Card animation ── */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card-animate { animation: fadeSlideUp 0.4s ease both; }
        .card-img-wrap:hover .card-overlay { opacity: 1 !important; }

        /* ── Detail button shimmer ── */
        .btn-detail { position: relative; overflow: hidden; }
        .btn-detail::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(90deg, transparent, rgba(77,70,46,0.06), transparent);
            transform: translateX(-100%);
            transition: transform 0.4s ease;
        }
        .btn-detail:hover::before { transform: translateX(100%); }

        /* ══════════════════════════════════════════
           DETAIL MODAL — REDESIGNED
           ══════════════════════════════════════════

           Strategy: foto tampil dalam kotak persegi
           berukuran tetap di kiri, gunakan object-contain
           sehingga gambar apapun rasionya tidak terpotong.
           Info scrollable di panel kanan.
        */

        /* Outer container */
        .dm-wrap {
            position: relative;
            width: 100%;
            max-width: 820px;
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 28px 70px rgba(27,28,26,0.24);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            /* Cap height so it never overflows viewport */
            max-height: calc(100vh - 48px);
        }

        /* Top bar */
        .dm-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            background: #faf9f5;
            border-bottom: 1px solid #e0d9c8;
            flex-shrink: 0;
        }

        /* Body = photo-panel + info-panel side by side */
        .dm-body {
            display: flex;
            flex-direction: column;      /* mobile: stacked */
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }
        @media (min-width: 640px) {
            .dm-body { flex-direction: row; }
        }

        /* ── Photo panel (left) ── */
        .dm-photo {
            background: #f0ece0;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        /* Mobile: fixed height */
        @media (max-width: 639px) {
            .dm-photo { height: 260px; }
        }
        /* Desktop: fixed width, stretches to body height */
        @media (min-width: 640px) {
            .dm-photo { width: 320px; }
        }
        @media (min-width: 768px) {
            .dm-photo { width: 360px; }
        }

        /* Stage: where the main photo lives */
        .dm-stage {
            flex: 1;
            min-height: 0;
            position: relative;
            overflow: hidden;
            /*
              Use a checkerboard pattern so transparent PNGs
              look intentional rather than dirty.
            */
            background-color: #ece8d7;
            background-image:
                linear-gradient(45deg, #e4e0ce 25%, transparent 25%),
                linear-gradient(-45deg, #e4e0ce 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #e4e0ce 75%),
                linear-gradient(-45deg, transparent 75%, #e4e0ce 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* The main photo itself — NEVER crops */
        .dm-stage-img {
            max-width: calc(100% - 28px);
            max-height: calc(100% - 28px);
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            border-radius: 6px;
            /* Gentle drop shadow to separate image from bg */
            box-shadow: 0 2px 16px rgba(27,28,26,0.10);
            transition: opacity 0.2s ease;
        }

        /* Nav arrows */
        .dm-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.90);
            border: 1px solid rgba(77,70,46,0.18);
            border-radius: 9px;
            color: #2e2a1e;
            cursor: pointer;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }
        .dm-arrow:hover { background: #4d462e; color: #F2E8C6; border-color: #4d462e; }
        .dm-arrow-l { left: 8px; }
        .dm-arrow-r { right: 8px; }

        /* Counter pill */
        .dm-counter {
            position: absolute;
            bottom: 8px; right: 8px;
            background: rgba(27,28,26,0.50);
            backdrop-filter: blur(4px);
            color: #fff;
            font-size: 10px; font-weight: 700;
            letter-spacing: 0.06em;
            padding: 3px 9px;
            border-radius: 20px;
            pointer-events: none;
        }

        /* Status badge on photo */
        .dm-status {
            position: absolute;
            top: 8px; left: 8px;
            font-size: 9px; font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 20px;
            z-index: 4;
        }

        /* Thumbnail strip */
        .dm-thumbs {
            display: flex;
            gap: 6px;
            padding: 8px 10px;
            background: #e4dfce;
            overflow-x: auto;
            flex-shrink: 0;
        }
        .dm-thumbs::-webkit-scrollbar { height: 3px; }
        .dm-thumbs::-webkit-scrollbar-track { background: #e4dfce; }
        .dm-thumbs::-webkit-scrollbar-thumb { background: #b8b09a; border-radius: 3px; }

        .dm-thumb {
            width: 50px; height: 50px;
            flex-shrink: 0;
            border-radius: 7px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.2s, opacity 0.15s, transform 0.15s;
            background: #d6d0bc;
        }
        .dm-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .dm-thumb-active { border-color: #4d462e !important; opacity: 1 !important; transform: scale(1.05); }
        .dm-thumb:not(.dm-thumb-active) { opacity: 0.45; }
        .dm-thumb:not(.dm-thumb-active):hover { opacity: 0.8; }

        /* ── Info panel (right) ── */
        .dm-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            min-width: 0;
            overflow: hidden;
        }
        .dm-info-body {
            flex: 1;
            overflow-y: auto;
            min-height: 0;
            padding: 20px 22px;
        }
        .dm-info-footer {
            flex-shrink: 0;
            padding: 14px 22px;
            background: #faf9f5;
            border-top: 1px solid #e0d9c8;
        }
    </style>
</head>

<body class="bg-[#f5f3ed] text-[#1b1c1a] antialiased">
    @include('user.components.navbar')

    <main x-data="{
        openFilter: false,
        detailOpen: false,
        activePhoto: 0,
        item: null,
        openDetail(data) {
            this.item = data;
            this.activePhoto = 0;
            this.detailOpen = true;
            document.body.style.overflow = 'hidden';
        },
        closeDetail() {
            this.detailOpen = false;
            document.body.style.overflow = '';
        },
        prevPhoto() {
            if (!this.item || !this.item.photos.length) return;
            this.activePhoto = (this.activePhoto - 1 + this.item.photos.length) % this.item.photos.length;
        },
        nextPhoto() {
            if (!this.item || !this.item.photos.length) return;
            this.activePhoto = (this.activePhoto + 1) % this.item.photos.length;
        }
    }" class="flex min-h-screen">

        {{-- Mobile filter overlay --}}
        <div x-show="openFilter"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            x-on:click="openFilter = false"
            class="fixed inset-0 bg-black/40 z-40 lg:hidden" x-cloak></div>

        @include('user.components.sidebar-katalog', ['kategori' => $kategori])

        {{-- ══ MAIN CONTENT ══ --}}
        <section class="flex-1 min-w-0 p-6 md:p-8">

            {{-- Header --}}
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-5 mb-8">
                <div>
                    <button x-on:click="openFilter = true"
                        class="lg:hidden flex items-center gap-2 mb-4 px-4 py-2.5 bg-[#4d462e] text-[#F2E8C6] rounded-xl font-syne font-bold text-[10px] tracking-[0.1em] uppercase hover:bg-[#2e2a1e] transition-colors">
                        <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                        Filter &amp; Kategori
                    </button>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="h-px w-8 bg-[#4d462e]"></div>
                        <span class="font-syne text-[9px] font-bold tracking-[0.3em] uppercase text-[#7b6f52]">Majelis Rental</span>
                    </div>
                    <h1 class="font-syne font-extrabold text-4xl md:text-5xl uppercase tracking-tight leading-none mb-2">
                        Daftar <span class="text-[#4d462e]">Gear</span>
                    </h1>
                    <p class="text-[11px] text-[#7b6f52] tracking-[0.1em] uppercase">
                        {{ $barang->firstItem() ?? 0 }}–{{ $barang->lastItem() ?? 0 }}
                        dari {{ $barang->total() }} item tersedia
                    </p>
                </div>
                <div class="flex gap-3 flex-wrap items-center">
                    <div class="flex items-center gap-2 bg-white border border-[#e0d9c8] rounded-xl px-3.5 py-2.5 hover:border-[#c8bfa8] transition-colors">
                        <span class="material-symbols-outlined text-[16px] text-[#7b6f52]">grid_view</span>
                        <span class="font-syne text-[9px] font-bold tracking-[0.1em] uppercase text-[#9b947c]">Tampil:</span>
                        <select class="bg-transparent border-none font-syne text-[12px] font-bold uppercase tracking-wider text-[#1b1c1a] focus:outline-none cursor-pointer"
                            x-data x-on:change="const u=new URL(window.location);u.searchParams.set('perPage',$event.target.value);u.searchParams.delete('page');window.location=u.toString()">
                            <option value="6"  {{ request('perPage','6')=='6'  ? 'selected':'' }}>6</option>
                            <option value="12" {{ request('perPage')=='12'      ? 'selected':'' }}>12</option>
                            <option value="24" {{ request('perPage')=='24'      ? 'selected':'' }}>24</option>
                            <option value="48" {{ request('perPage')=='48'      ? 'selected':'' }}>48</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 bg-white border border-[#e0d9c8] rounded-xl px-3.5 py-2.5 hover:border-[#c8bfa8] transition-colors">
                        <span class="material-symbols-outlined text-[16px] text-[#7b6f52]">sort</span>
                        <span class="font-syne text-[9px] font-bold tracking-[0.1em] uppercase text-[#9b947c]">Urutkan:</span>
                        <select class="bg-transparent border-none font-syne text-[10px] font-bold uppercase tracking-wider text-[#1b1c1a] focus:outline-none cursor-pointer"
                            x-data x-on:change="const u=new URL(window.location);u.searchParams.set('sort',$event.target.value);u.searchParams.delete('page');window.location=u.toString()">
                            <option value="terbaru"    {{ request('sort','terbaru')==='terbaru'    ? 'selected':'' }}>Terbaru</option>
                            <option value="harga_asc"  {{ request('sort')==='harga_asc'            ? 'selected':'' }}>Harga ↑</option>
                            <option value="harga_desc" {{ request('sort')==='harga_desc'           ? 'selected':'' }}>Harga ↓</option>
                            <option value="nama_asc"   {{ request('sort')==='nama_asc'             ? 'selected':'' }}>Nama A–Z</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Active filter chips --}}
            @if (request()->hasAny(['kategori','harga','search']))
                <div class="flex flex-wrap items-center gap-2 mb-6">
                    <span class="text-[9px] font-bold tracking-[0.18em] uppercase text-[#9b947c]">Filter aktif:</span>
                    @if (request('search'))
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#4d462e]/8 border border-[#4d462e]/20 rounded-full text-[10px] font-semibold text-[#4d462e]">
                            <span class="material-symbols-outlined text-[12px]">search</span>
                            "{{ request('search') }}"
                        </span>
                    @endif
                    @foreach ((array) request('kategori', []) as $katId)
                        @php $k = $kategori->firstWhere('id', $katId); @endphp
                        @if ($k)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#4d462e]/8 border border-[#4d462e]/20 rounded-full text-[10px] font-semibold text-[#4d462e]">
                                @if ($k->ikon)<span class="material-symbols-outlined text-[12px]">{{ $k->ikon }}</span>@endif
                                {{ $k->nama }}
                            </span>
                        @endif
                    @endforeach
                    @if (request('harga'))
                        <span class="inline-flex items-center px-3 py-1 bg-[#4d462e]/8 border border-[#4d462e]/20 rounded-full text-[10px] font-semibold text-[#4d462e]">
                            ≤ Rp {{ number_format((int) request('harga'), 0, ',', '.') }}
                        </span>
                    @endif
                    <a href="{{ route('katalog', request()->only('sort','perPage')) }}"
                        class="inline-flex items-center gap-1 text-[10px] font-bold text-red-500 hover:text-red-700 transition-colors">
                        <span class="material-symbols-outlined text-[14px]">cancel</span>Hapus semua
                    </a>
                </div>
            @endif

            {{-- ══ PRODUCT GRID ══ --}}
            @if ($barang->isEmpty())
                <div class="flex flex-col items-center justify-center py-28 text-center">
                    <span class="material-symbols-outlined text-6xl text-[#c8bfa8] block mb-4">inventory_2</span>
                    <h3 class="font-syne font-extrabold text-xl uppercase text-[#1b1c1a] mb-2">Tidak Ada Item</h3>
                    <p class="text-sm text-[#7b6f52] mb-6">Tidak ada barang yang cocok dengan filter yang dipilih.</p>
                    <a href="{{ route('katalog') }}"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-[#4d462e] text-[#F2E8C6] rounded-xl font-syne font-bold text-[10px] tracking-[0.12em] uppercase hover:bg-[#2e2a1e] transition-colors">
                        Reset Filter
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach ($barang as $index => $item)
                        @php
                            $allPhotos = collect($item->fotos)->map(fn($f)=>asset('storage/'.$f->path_foto))->values()->toArray();
                            if (empty($allPhotos)) {
                                $allPhotos = [$item->fotoUtama && $item->fotoUtama->path_foto
                                    ? asset('storage/'.$item->fotoUtama->path_foto)
                                    : asset('images/no-image.png')];
                            }
                            $itemData = [
                                'id'           => $item->id,
                                'nama'         => $item->nama,
                                'deskripsi'    => $item->deskripsi ?? '',
                                'spesifikasi'  => $item->spesifikasi ?? '',
                                'harga'        => number_format($item->harga_per_hari, 0, ',', '.'),
                                'stok'         => $item->stok,
                                'kategori'     => $item->kategori?->nama ?? '',
                                'kategoriIkon' => $item->kategori?->ikon ?? '',
                                'photos'       => $allPhotos ?: [asset('images/no-image.png')],
                                'checkoutUrl'  => route('checkout.index'),
                                'tambahUrl'    => route('keranjang.tambah', $item->id),
                            ];
                        @endphp

                        <div x-data="{
                                loading: false, added: false,
                                async addToCart() {
                                    if (this.loading) return;
                                    this.loading = true;
                                    try {
                                        const res = await fetch('{{ route('keranjang.tambah', $item->id) }}', {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                'Accept': 'application/json',
                                            },
                                        });
                                        const data = await res.json();
                                        if (res.ok && data.success) {
                                            $store.cart.items = data.cart;
                                            this.added = true;
                                            Alpine.store('toast').flash(data.message, 'success');
                                            setTimeout(() => this.added = false, 2500);
                                        } else {
                                            Alpine.store('toast').flash(data.message ?? 'Gagal menambahkan item.', 'error');
                                        }
                                    } catch (e) {
                                        Alpine.store('toast').flash('Terjadi kesalahan.', 'error');
                                    } finally {
                                        this.loading = false;
                                    }
                                }
                            }"
                            class="product-card bg-white border border-[#e0d9c8] rounded-2xl overflow-hidden flex flex-col transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-[#4d462e]/10 hover:border-[#c8bfa8] card-animate"
                            style="animation-delay: {{ $index * 0.04 }}s">

                            <div class="card-img-wrap relative overflow-hidden bg-[#eeead8] aspect-[4/3]">
                                <img src="{{ $item->fotoUtama && $item->fotoUtama->path_foto ? asset('storage/'.$item->fotoUtama->path_foto) : asset('images/no-image.png') }}"
                                    alt="{{ $item->nama }}" loading="lazy" class="w-full h-full object-cover">
                                <div class="card-overlay absolute inset-0 bg-gradient-to-t from-[#1b1c1a]/30 to-transparent opacity-0 transition-opacity duration-300"></div>
                                <div class="absolute top-3 left-3 z-10">
                                    @if ($item->stok > 0)
                                        <span class="font-syne font-bold text-[9px] tracking-[0.1em] uppercase px-3 py-1.5 rounded-full bg-[#4d462e] text-[#F2E8C6]">Tersedia</span>
                                    @else
                                        <span class="font-syne font-bold text-[9px] tracking-[0.1em] uppercase px-3 py-1.5 rounded-full bg-red-600 text-white">Habis</span>
                                    @endif
                                </div>
                                @if ($item->stok > 0 && $item->stok <= 3)
                                    <div class="absolute top-3 right-3 z-10">
                                        <span class="font-syne font-bold text-[9px] tracking-[0.1em] uppercase px-2.5 py-1.5 rounded-full bg-amber-500 text-white">Sisa {{ $item->stok }}</span>
                                    </div>
                                @endif
                                @if (collect($item->fotos)->count() > 1)
                                    <div class="absolute bottom-3 right-3 z-10">
                                        <span class="flex items-center gap-1 font-syne font-bold text-[9px] tracking-[0.08em] uppercase px-2.5 py-1.5 rounded-full bg-black/50 text-white backdrop-blur-sm">
                                            <span class="material-symbols-outlined text-[11px]">photo_library</span>
                                            {{ collect($item->fotos)->count() }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <div class="p-5 flex flex-col flex-1">
                                @if ($item->kategori)
                                    <div class="inline-flex items-center gap-1.5 self-start px-3 py-1 rounded-full bg-[#4d462e]/10 border border-[#4d462e]/20 text-[11px] font-semibold text-[#4d462e] tracking-wide uppercase mb-3">
                                        @if ($item->kategori->ikon)
                                            <span class="w-4 h-4 flex items-center justify-center">@includeIf('icons.'.$item->kategori->ikon)</span>
                                        @endif
                                        <span class="leading-none">{{ $item->kategori->nama }}</span>
                                    </div>
                                @endif
                                <h3 class="font-syne font-extrabold text-[17px] uppercase tracking-tight leading-snug text-[#1b1c1a] mb-2 line-clamp-2">{{ $item->nama }}</h3>
                                <div class="font-syne font-extrabold text-[22px] text-[#4d462e] leading-none mb-4">
                                    Rp {{ number_format($item->harga_per_hari, 0, ',', '.') }}
                                    <span class="font-sans font-normal text-sm text-[#7b6f52]">/hari</span>
                                </div>
                                <button type="button" @click="openDetail({{ Js::from($itemData) }})"
                                    class="btn-detail w-full mb-4 flex items-center justify-between gap-3 px-4 py-3 rounded-xl border border-[#e0d9c8] bg-[#faf9f5] hover:border-[#4d462e] hover:bg-[#4d462e]/5 transition-all duration-200 group">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-[#4d462e]/10 flex items-center justify-center group-hover:bg-[#4d462e]/20 transition-colors duration-200">
                                            <span class="material-symbols-outlined text-[14px] text-[#4d462e]">info</span>
                                        </div>
                                        <div class="text-left">
                                            <p class="font-syne font-bold text-[10px] tracking-[0.1em] uppercase text-[#4d462e] leading-none">Lihat Detail</p>
                                            <p class="text-[9px] text-[#9b947c] mt-0.5 leading-none">Deskripsi &amp; Spesifikasi</p>
                                        </div>
                                    </div>
                                    <span class="material-symbols-outlined text-[16px] text-[#c8bfa8] group-hover:text-[#4d462e] group-hover:translate-x-0.5 transition-all duration-200">chevron_right</span>
                                </button>
                                <div class="flex items-center gap-1.5 text-[11px] text-[#7b6f52] mb-4">
                                    <span class="material-symbols-outlined text-[14px]">inventory_2</span>
                                    Stok: <strong class="text-[#1b1c1a] ml-0.5">{{ $item->stok }}</strong>
                                    @if ($item->stok > 0 && $item->stok <= 3)
                                        <span class="text-amber-600 font-semibold">— menipis!</span>
                                    @endif
                                </div>
                                <div class="flex flex-col gap-2 mt-auto">
                                    @if ($item->stok > 0)
                                        <a href="{{ route('checkout.index') }}"
                                            class="flex items-center justify-center gap-2 py-3 bg-[#2e2a1e] text-white rounded-xl font-syne font-bold text-[10px] tracking-[0.12em] uppercase hover:bg-[#4d462e] hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200">
                                            <span class="material-symbols-outlined text-[14px]">bolt</span>Sewa Sekarang
                                        </a>
                                        <button type="button" x-on:click="addToCart" :disabled="loading"
                                            class="flex items-center justify-center gap-2 py-3 border-[1.5px] rounded-xl font-syne font-bold text-[10px] tracking-[0.12em] uppercase transition-all duration-200 hover:-translate-y-0.5"
                                            :class="added ? 'border-green-500 bg-green-50 text-green-700' : 'border-[#c8bfa8] text-[#2e2a1e] hover:bg-[#2e2a1e] hover:text-white hover:border-[#2e2a1e]'">
                                            <template x-if="loading"><span class="w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full animate-spin"></span></template>
                                            <template x-if="!loading && added"><span class="material-symbols-outlined text-[14px]">check_circle</span></template>
                                            <template x-if="!loading && !added"><span class="material-symbols-outlined text-[14px]">shopping_bag</span></template>
                                            <span x-text="loading ? 'Menambahkan...' : (added ? 'Ditambahkan!' : 'Keranjang')"></span>
                                        </button>
                                    @else
                                        <div class="flex items-center justify-center gap-2 py-3 bg-[#f0ece0] text-[#9b947c] border border-[#e0d9c8] rounded-xl font-syne font-bold text-[10px] tracking-[0.12em] uppercase cursor-not-allowed">
                                            <span class="material-symbols-outlined text-[14px]">remove_shopping_cart</span>Stok Habis
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if ($barang->hasPages())
                    <div class="mt-14 flex flex-col items-center gap-4">
                        <p class="text-[10px] text-[#9b947c] tracking-[0.15em] uppercase">
                            Halaman {{ $barang->currentPage() }} dari {{ $barang->lastPage() }}
                            &nbsp;·&nbsp; {{ $barang->total() }} item total
                        </p>
                        <nav class="flex items-center gap-2">
                            @if ($barang->onFirstPage())
                                <span class="w-10 h-10 flex items-center justify-center rounded-xl border border-[#e0d9c8] text-[#c8bfa8] cursor-not-allowed">
                                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                                </span>
                            @else
                                <a href="{{ $barang->previousPageUrl() }}"
                                    class="w-10 h-10 flex items-center justify-center rounded-xl border border-[#e0d9c8] text-[#5a584f] hover:bg-[#4d462e] hover:text-[#F2E8C6] hover:border-[#4d462e] transition-all">
                                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                                </a>
                            @endif
                            @php
                                $current = $barang->currentPage();
                                $last    = $barang->lastPage();
                                $pages   = collect([1,$last]);
                                for($i=max(2,$current-2);$i<=min($last-1,$current+2);$i++) $pages->push($i);
                                $pages=$pages->unique()->sort()->values(); $prev=null;
                            @endphp
                            @foreach ($pages as $page)
                                @if ($prev!==null && $page-$prev>1)<span class="text-[#9b947c] px-1">···</span>@endif
                                @if ($page==$current)
                                    <span class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#4d462e] text-[#F2E8C6] font-syne font-bold text-[12px]">{{ str_pad($page,2,'0',STR_PAD_LEFT) }}</span>
                                @else
                                    <a href="{{ $barang->url($page) }}" class="w-10 h-10 flex items-center justify-center rounded-xl border border-[#e0d9c8] text-[#5a584f] font-syne font-bold text-[12px] hover:bg-[#4d462e] hover:text-[#F2E8C6] hover:border-[#4d462e] transition-all">{{ str_pad($page,2,'0',STR_PAD_LEFT) }}</a>
                                @endif
                                @php $prev=$page; @endphp
                            @endforeach
                            @if ($barang->hasMorePages())
                                <a href="{{ $barang->nextPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-xl border border-[#e0d9c8] text-[#5a584f] hover:bg-[#4d462e] hover:text-[#F2E8C6] hover:border-[#4d462e] transition-all">
                                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                                </a>
                            @else
                                <span class="w-10 h-10 flex items-center justify-center rounded-xl border border-[#e0d9c8] text-[#c8bfa8] cursor-not-allowed">
                                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                                </span>
                            @endif
                        </nav>
                    </div>
                @endif
            @endif

        </section>


        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{--                    DETAIL MODAL (NEW LAYOUT)                  --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="detailOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5"
            @keydown.escape.window="closeDetail()">

            {{-- Backdrop --}}
            <div x-show="detailOpen"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                @click="closeDetail()"
                class="absolute inset-0 bg-[#1b1c1a]/65 backdrop-blur-sm"></div>

            {{-- Modal --}}
            <div x-show="detailOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="dm-wrap">

                {{-- Top bar --}}
                <div class="dm-topbar">
                    <div class="flex items-center gap-2.5">
                        <div class="h-px w-5 bg-[#4d462e]"></div>
                        <span class="font-syne text-[9px] font-bold tracking-[0.3em] uppercase text-[#7b6f52]">Detail Gear</span>
                        {{-- Item name preview in topbar --}}
                        <template x-if="item">
                            <span class="hidden sm:inline text-[10px] text-[#c8bfa8]">·</span>
                        </template>
                        <template x-if="item">
                            <span class="hidden sm:inline font-syne font-bold text-[10px] uppercase tracking-wide text-[#4d462e] truncate max-w-[200px]" x-text="item.nama"></span>
                        </template>
                    </div>
                    <button @click="closeDetail()"
                        class="w-8 h-8 flex items-center justify-center rounded-xl border border-[#e0d9c8] text-[#5a584f] hover:bg-[#4d462e] hover:text-[#F2E8C6] hover:border-[#4d462e] transition-all duration-200 shrink-0">
                        <span class="material-symbols-outlined text-[17px]">close</span>
                    </button>
                </div>

                {{-- Body --}}
                <div class="dm-body">

                    {{-- ══ LEFT: Photo panel ══
                         Key design decisions:
                         1. Fixed width (320–360px) so it doesn't depend on image AR
                         2. dm-stage uses display:flex + align-items:center + justify-content:center
                         3. Image uses max-width / max-height with object-fit:contain
                            → image is ALWAYS shown in full, never cropped
                         4. Checkerboard bg makes transparent areas obvious / intentional
                    --}}
                    <div class="dm-photo">

                        <div class="dm-stage">
                            <template x-if="item">
                                <img :src="item.photos[activePhoto] ?? item.photos[0]"
                                    :alt="item.nama"
                                    class="dm-stage-img">
                            </template>

                            {{-- Availability badge --}}
                            <template x-if="item">
                                <div class="dm-status"
                                    :class="item.stok > 0 ? 'bg-[#4d462e] text-[#F2E8C6]' : 'bg-red-600 text-white'"
                                    x-text="item.stok > 0 ? 'Tersedia' : 'Habis'">
                                </div>
                            </template>

                            {{-- Arrows --}}
                            <template x-if="item && item.photos.length > 1">
                                <button @click.stop="prevPhoto()" class="dm-arrow dm-arrow-l">
                                    <span class="material-symbols-outlined" style="font-size:17px">chevron_left</span>
                                </button>
                            </template>
                            <template x-if="item && item.photos.length > 1">
                                <button @click.stop="nextPhoto()" class="dm-arrow dm-arrow-r">
                                    <span class="material-symbols-outlined" style="font-size:17px">chevron_right</span>
                                </button>
                            </template>

                            {{-- Counter --}}
                            <template x-if="item && item.photos.length > 1">
                                <div class="dm-counter" x-text="(activePhoto+1)+' / '+item.photos.length"></div>
                            </template>
                        </div>

                        {{-- Thumbnail strip --}}
                        <template x-if="item && item.photos.length > 1">
                            <div class="dm-thumbs">
                                <template x-for="(photo, idx) in item.photos" :key="idx">
                                    <div @click="activePhoto = idx"
                                        :class="activePhoto === idx ? 'dm-thumb dm-thumb-active' : 'dm-thumb'">
                                        <img :src="photo" loading="lazy">
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- ══ RIGHT: Info panel ══ --}}
                    <div class="dm-info">

                        {{-- Scrollable info --}}
                        <div class="dm-info-body modal-scroll">
                            <template x-if="item">
                                <div class="space-y-4">

                                    {{-- Badges --}}
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            :class="item.stok > 0 ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-red-50 border-red-200 text-red-600'"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[10px] font-bold tracking-wide uppercase">
                                            <span class="material-symbols-outlined" style="font-size:11px">inventory_2</span>
                                            <span x-text="item.stok > 0 ? 'Stok: ' + item.stok : 'Stok Habis'"></span>
                                        </span>
                                        <template x-if="item.stok > 0 && item.stok <= 3">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-[10px] font-bold text-amber-700 tracking-wide uppercase">
                                                <span class="material-symbols-outlined" style="font-size:11px">warning</span>
                                                Menipis!
                                            </span>
                                        </template>
                                    </div>

                                    {{-- Name --}}
                                    <h2 class="font-syne font-extrabold text-[18px] sm:text-[22px] uppercase tracking-tight leading-tight text-[#1b1c1a]"
                                        x-text="item.nama"></h2>

                                    {{-- Price --}}
                                    <div class="flex items-end gap-2 pb-4 border-b border-[#e0d9c8]">
                                        <span class="font-syne font-extrabold text-[24px] sm:text-[28px] text-[#4d462e] leading-none" x-text="'Rp '+item.harga"></span>
                                        <span class="text-sm text-[#7b6f52] mb-0.5">/hari</span>
                                    </div>

                                    {{-- Deskripsi --}}
                                    <template x-if="item.deskripsi">
                                        <div>
                                            <div class="flex items-center gap-1.5 mb-2">
                                                <span class="material-symbols-outlined text-[#4d462e]" style="font-size:14px">description</span>
                                                <h4 class="font-syne font-bold text-[10px] tracking-[0.2em] uppercase text-[#4d462e]">Deskripsi</h4>
                                            </div>
                                            <p class="text-[13px] text-[#5a584f] leading-relaxed" x-text="item.deskripsi"></p>
                                        </div>
                                    </template>

                                    {{-- Spesifikasi --}}
                                    <template x-if="item.spesifikasi">
                                        <div>
                                            <div class="flex items-center gap-1.5 mb-2">
                                                <span class="material-symbols-outlined text-[#4d462e]" style="font-size:14px">checklist</span>
                                                <h4 class="font-syne font-bold text-[10px] tracking-[0.2em] uppercase text-[#4d462e]">Spesifikasi</h4>
                                            </div>
                                            <div class="bg-[#f5f3ed] border border-[#e0d9c8] rounded-xl p-3.5">
                                                <template x-for="(line,i) in item.spesifikasi.split('\n').filter(l=>l.trim())" :key="i">
                                                    <div class="spec-line">
                                                        <div class="spec-bullet"></div>
                                                        <span x-text="line.trim()"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Empty --}}
                                    <template x-if="!item.deskripsi && !item.spesifikasi">
                                        <div class="flex flex-col items-center py-10 text-center">
                                            <span class="material-symbols-outlined text-[#e0d9c8]" style="font-size:48px">info</span>
                                            <p class="text-[12px] font-semibold text-[#c8bfa8] mt-3">Belum ada deskripsi atau spesifikasi.</p>
                                        </div>
                                    </template>

                                </div>
                            </template>
                        </div>

                        {{-- Action buttons footer --}}
                        <div class="dm-info-footer">
                            <template x-if="item && item.stok > 0">
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <a :href="item.checkoutUrl"
                                        class="flex-1 flex items-center justify-center gap-2 py-3 bg-[#2e2a1e] text-white rounded-xl font-syne font-bold text-[10px] tracking-[0.12em] uppercase hover:bg-[#4d462e] hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200">
                                        <span class="material-symbols-outlined" style="font-size:14px">bolt</span>
                                        Sewa Sekarang
                                    </a>
                                    <button type="button" x-data="{ busy:false, done:false }"
                                        @click="
                                            if(busy) return; busy=true;
                                            fetch(item.tambahUrl,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}})
                                            .then(r=>r.json())
                                            .then(d=>{
                                                if(d.success){$store.cart.items=d.cart;Alpine.store('toast').flash(d.message,'success');done=true;setTimeout(()=>done=false,2500);}
                                                else{Alpine.store('toast').flash(d.message??'Gagal.','error');}
                                            })
                                            .catch(()=>Alpine.store('toast').flash('Terjadi kesalahan.','error'))
                                            .finally(()=>busy=false)
                                        "
                                        :class="done ? 'border-green-500 bg-green-50 text-green-700' : 'border-[#c8bfa8] text-[#2e2a1e] hover:bg-[#2e2a1e] hover:text-white hover:border-[#2e2a1e]'"
                                        class="flex-1 flex items-center justify-center gap-2 py-3 border-[1.5px] rounded-xl font-syne font-bold text-[10px] tracking-[0.12em] uppercase transition-all duration-200 hover:-translate-y-0.5">
                                        <template x-if="busy"><span class="w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full animate-spin"></span></template>
                                        <template x-if="!busy && done"><span class="material-symbols-outlined" style="font-size:14px">check_circle</span></template>
                                        <template x-if="!busy && !done"><span class="material-symbols-outlined" style="font-size:14px">shopping_bag</span></template>
                                        <span x-text="busy ? 'Menambahkan...' : (done ? 'Ditambahkan!' : 'Tambah Keranjang')"></span>
                                    </button>
                                </div>
                            </template>
                            <template x-if="item && item.stok <= 0">
                                <div class="flex items-center justify-center gap-2 py-3 bg-[#f0ece0] text-[#9b947c] border border-[#e0d9c8] rounded-xl font-syne font-bold text-[10px] tracking-[0.12em] uppercase cursor-not-allowed">
                                    <span class="material-symbols-outlined" style="font-size:14px">remove_shopping_cart</span>
                                    Stok Habis
                                </div>
                            </template>
                        </div>

                    </div>
                    {{-- end info panel --}}

                </div>
                {{-- end dm-body --}}

            </div>
            {{-- end dm-wrap --}}

        </div>
        {{-- ══ END DETAIL MODAL ══ --}}

    </main>

    @include('user.components.footer')
</body>

</html>
