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
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800;900&family=Space+Grotesk:wght@700&display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap"
        rel="stylesheet" />
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

</head>

<body class="bg-[#f5f3ed] text-[#1b1c1a] antialiased">
    @include('user.components.navbar')

    <main x-data="{ openFilter: false }" class="flex min-h-screen">

        {{-- ── Mobile overlay ── --}}
        <div x-show="openFilter" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" x-on:click="openFilter = false"
            class="fixed inset-0 bg-black/40 z-40 lg:hidden" x-cloak></div>

        @include('user.components.sidebar-katalog', ['kategori' => $kategori])

        {{-- ══════════ MAIN CONTENT ══════════ --}}
        <section class="flex-1 min-w-0 p-6 md:p-8">

            {{-- HEADER --}}
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-5 mb-8">
                <div>
                    {{-- Mobile filter btn --}}
                    <button x-on:click="openFilter = true"
                        class="lg:hidden flex items-center gap-2 mb-4 px-4 py-2.5 bg-[#4d462e] text-[#F2E8C6] rounded-xl font-syne font-bold text-[10px] tracking-[0.1em] uppercase hover:bg-[#2e2a1e] transition-colors">
                        <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                        Filter &amp; Kategori
                    </button>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="h-px w-8 bg-[#4d462e]"></div>
                        <span class="font-syne text-[9px] font-bold tracking-[0.3em] uppercase text-[#7b6f52]">Majelis
                            Rental</span>
                    </div>
                    <h1
                        class="font-syne font-extrabold text-4xl md:text-5xl uppercase tracking-tight leading-none mb-2">
                        Daftar <span class="text-[#4d462e]">Gear</span>
                    </h1>
                    <p class="text-[11px] text-[#7b6f52] tracking-[0.1em] uppercase">
                        {{ $barang->firstItem() ?? 0 }}–{{ $barang->lastItem() ?? 0 }}
                        dari {{ $barang->total() }} item tersedia
                    </p>
                </div>

                {{-- Sort & Per page --}}
                <div class="flex gap-3 flex-wrap items-center">
                    <div
                        class="flex items-center gap-2 bg-white border border-[#e0d9c8] rounded-xl px-3.5 py-2.5 hover:border-[#c8bfa8] transition-colors">
                        <span class="material-symbols-outlined text-[16px] text-[#7b6f52]">grid_view</span>
                        <span
                            class="font-syne text-[9px] font-bold tracking-[0.1em] uppercase text-[#9b947c]">Tampil:</span>
                        <select
                            class="bg-transparent border-none font-syne text-[10px] font-bold uppercase tracking-wider text-[#1b1c1a] focus:outline-none cursor-pointer"
                            x-data
                            x-on:change="const u=new URL(window.location);u.searchParams.set('perPage',$event.target.value);u.searchParams.delete('page');window.location=u.toString()">
                            <option value="6" {{ request('perPage', '6') == '6' ? 'selected' : '' }}>6</option>
                            <option value="12" {{ request('perPage') == '12' ? 'selected' : '' }}>12</option>
                            <option value="24" {{ request('perPage') == '24' ? 'selected' : '' }}>24</option>
                            <option value="48" {{ request('perPage') == '48' ? 'selected' : '' }}>48</option>
                        </select>
                    </div>
                    <div
                        class="flex items-center gap-2 bg-white border border-[#e0d9c8] rounded-xl px-3.5 py-2.5 hover:border-[#c8bfa8] transition-colors">
                        <span class="material-symbols-outlined text-[16px] text-[#7b6f52]">sort</span>
                        <span
                            class="font-syne text-[9px] font-bold tracking-[0.1em] uppercase text-[#9b947c]">Urutkan:</span>
                        <select
                            class="bg-transparent border-none font-syne text-[10px] font-bold uppercase tracking-wider text-[#1b1c1a] focus:outline-none cursor-pointer"
                            x-data
                            x-on:change="const u=new URL(window.location);u.searchParams.set('sort',$event.target.value);u.searchParams.delete('page');window.location=u.toString()">
                            <option value="terbaru" {{ request('sort', 'terbaru') === 'terbaru' ? 'selected' : '' }}>
                                Terbaru</option>
                            <option value="harga_asc" {{ request('sort') === 'harga_asc' ? 'selected' : '' }}>Harga ↑
                            </option>
                            <option value="harga_desc" {{ request('sort') === 'harga_desc' ? 'selected' : '' }}>Harga ↓
                            </option>
                            <option value="nama_asc" {{ request('sort') === 'nama_asc' ? 'selected' : '' }}>Nama A–Z
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Active filter chips --}}
            @if (request()->hasAny(['kategori', 'harga', 'search']))
                <div class="flex flex-wrap items-center gap-2 mb-6">
                    <span class="text-[9px] font-bold tracking-[0.18em] uppercase text-[#9b947c]">Filter aktif:</span>
                    @if (request('search'))
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#4d462e]/8 border border-[#4d462e]/20 rounded-full text-[10px] font-semibold text-[#4d462e]">
                            <span class="material-symbols-outlined text-[12px]">search</span>
                            "{{ request('search') }}"
                        </span>
                    @endif
                    @foreach ((array) request('kategori', []) as $katId)
                        @php $k = $kategori->firstWhere('id',$katId); @endphp
                        @if ($k)
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#4d462e]/8 border border-[#4d462e]/20 rounded-full text-[10px] font-semibold text-[#4d462e]">
                                @if ($k->ikon)
                                    <span class="material-symbols-outlined text-[12px]">{{ $k->ikon }}</span>
                                @endif
                                {{ $k->nama }}
                            </span>
                        @endif
                    @endforeach
                    @if (request('harga'))
                        <span
                            class="inline-flex items-center px-3 py-1 bg-[#4d462e]/8 border border-[#4d462e]/20 rounded-full text-[10px] font-semibold text-[#4d462e]">
                            ≤ Rp {{ number_format((int) request('harga'), 0, ',', '.') }}
                        </span>
                    @endif
                    <a href="{{ route('katalog', request()->only('sort', 'perPage')) }}"
                        class="inline-flex items-center gap-1 text-[10px] font-bold text-red-500 hover:text-red-700 transition-colors">
                        <span class="material-symbols-outlined text-[14px]">cancel</span>Hapus semua
                    </a>
                </div>
            @endif

            {{-- GRID --}}
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
                        <div x-data="{
                            loading: false,
                            added: false,
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

                            {{-- Image --}}
                            <div class="card-img-wrap relative overflow-hidden bg-[#eeead8] aspect-[4/3]">
                                <img src="{{ $item->fotoUtama && $item->fotoUtama->path_foto ? asset('storage/' . $item->fotoUtama->path_foto) : asset('images/no-image.png') }}"
                                    alt="{{ $item->nama }}" loading="lazy" class="w-full h-full object-cover">

                                {{-- Gradient overlay on hover --}}
                                <div
                                    class="card-overlay absolute inset-0 bg-gradient-to-t from-[#1b1c1a]/30 to-transparent opacity-0">
                                </div>

                                {{-- Status badge --}}
                                <div class="absolute top-3 left-3 z-10">
                                    @if ($item->stok > 0)
                                        <span
                                            class="font-syne font-bold text-[9px] tracking-[0.1em] uppercase px-3 py-1.5 rounded-full bg-[#4d462e] text-[#F2E8C6]">Tersedia</span>
                                    @else
                                        <span
                                            class="font-syne font-bold text-[9px] tracking-[0.1em] uppercase px-3 py-1.5 rounded-full bg-red-600 text-white">Habis</span>
                                    @endif
                                </div>

                                {{-- Low stock --}}
                                @if ($item->stok > 0 && $item->stok <= 3)
                                    <div class="absolute top-3 right-3 z-10">
                                        <span
                                            class="font-syne font-bold text-[9px] tracking-[0.1em] uppercase px-2.5 py-1.5 rounded-full bg-amber-500 text-white">
                                            Sisa {{ $item->stok }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- Body --}}
                            <div class="p-5 flex flex-col flex-1">

                                {{-- Kategori chip --}}
                                @if ($item->kategori)
                                    <div
                                        class="inline-flex items-center gap-1.5 self-start px-3 py-1 rounded-full
                                        bg-[#4d462e]/10 border border-[#4d462e]/20
                                        text-[11px] font-semibold text-[#4d462e]
                                        tracking-wide uppercase mb-3">

                                        {{-- ICON --}}
                                        @if ($item->kategori->ikon)
                                            <span class="w-4 h-4 flex items-center justify-center">
                                                @includeIf('icons.' . $item->kategori->ikon)
                                            </span>
                                        @endif

                                        {{-- TEXT --}}
                                        <span class="leading-none">
                                            {{ $item->kategori->nama }}
                                        </span>

                                    </div>
                                @endif

                                {{-- Name --}}
                                <h3
                                    class="font-syne font-extrabold text-[17px] uppercase tracking-tight leading-snug text-[#1b1c1a] mb-2 line-clamp-2">
                                    {{ $item->nama }}
                                </h3>

                                {{-- Price --}}
                                <div class="font-syne font-extrabold text-[22px] text-[#4d462e] leading-none mb-3">
                                    Rp {{ number_format($item->harga_per_hari, 0, ',', '.') }}
                                    <span class="font-sans font-normal text-sm text-[#7b6f52]">/hari</span>
                                </div>

                                {{-- Desc --}}
                                @if ($item->deskripsi)
                                    <p class="text-[12px] text-[#5a584f] leading-relaxed line-clamp-2 mb-3 flex-1">
                                        {{ $item->deskripsi }}
                                    </p>
                                @endif

                                {{-- Spec --}}
                                @if ($item->spesifikasi)
                                    <div
                                        class="text-[11px] text-[#5a584f] bg-[#f5f3ed] border border-[#e0d9c8] rounded-xl px-3 py-2 mb-3 line-clamp-2 leading-relaxed">
                                        {{ $item->spesifikasi }}
                                    </div>
                                @endif

                                {{-- Stock --}}
                                <div class="flex items-center gap-1.5 text-[11px] text-[#7b6f52] mb-4">
                                    <span class="material-symbols-outlined text-[14px]">inventory_2</span>
                                    Stok: <strong class="text-[#1b1c1a] ml-0.5">{{ $item->stok }}</strong>
                                    @if ($item->stok > 0 && $item->stok <= 3)
                                        <span class="text-amber-600 font-semibold">— menipis!</span>
                                    @endif
                                </div>

                                {{-- Actions --}}
                                <div class="flex flex-col gap-2 mt-auto">
                                    @if ($item->stok > 0)
                                        <a href="{{ route('checkout.index') }}"
                                            class="flex items-center justify-center gap-2 py-3 bg-[#2e2a1e] text-white rounded-xl font-syne font-bold text-[10px] tracking-[0.12em] uppercase hover:bg-[#4d462e] hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200">
                                            <span class="material-symbols-outlined text-[14px]">bolt</span>
                                            Sewa Sekarang
                                        </a>
                                        <button type="button" x-on:click="addToCart" :disabled="loading"
                                            class="flex items-center justify-center gap-2 py-3 border-[1.5px] rounded-xl font-syne font-bold text-[10px] tracking-[0.12em] uppercase transition-all duration-200 hover:-translate-y-0.5"
                                            :class="added
                                                ?
                                                'border-green-500 bg-green-50 text-green-700' :
                                                'border-[#c8bfa8] text-[#2e2a1e] hover:bg-[#2e2a1e] hover:text-white hover:border-[#2e2a1e]'">
                                            <template x-if="loading">
                                                <span
                                                    class="w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                            </template>
                                            <template x-if="!loading && added">
                                                <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                            </template>
                                            <template x-if="!loading && !added">
                                                <span class="material-symbols-outlined text-[14px]">shopping_bag</span>
                                            </template>
                                            <span
                                                x-text="loading ? 'Menambahkan...' : (added ? 'Ditambahkan!' : 'Keranjang')"></span>
                                        </button>
                                    @else
                                        <div
                                            class="flex items-center justify-center gap-2 py-3 bg-[#f0ece0] text-[#9b947c] border border-[#e0d9c8] rounded-xl font-syne font-bold text-[10px] tracking-[0.12em] uppercase cursor-not-allowed">
                                            <span
                                                class="material-symbols-outlined text-[14px]">remove_shopping_cart</span>
                                            Stok Habis
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- PAGINATION --}}
                @if ($barang->hasPages())
                    <div class="mt-14 flex flex-col items-center gap-4">
                        <p class="text-[10px] text-[#9b947c] tracking-[0.15em] uppercase">
                            Halaman {{ $barang->currentPage() }} dari {{ $barang->lastPage() }}
                            &nbsp;·&nbsp; {{ $barang->total() }} item total
                        </p>

                        <nav class="flex items-center gap-2">
                            @if ($barang->onFirstPage())
                                <span
                                    class="w-10 h-10 flex items-center justify-center rounded-xl border border-[#e0d9c8] text-[#c8bfa8] cursor-not-allowed">
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
                                $last = $barang->lastPage();
                                $pages = collect([1, $last]);
                                for ($i = max(2, $current - 2); $i <= min($last - 1, $current + 2); $i++) {
                                    $pages->push($i);
                                }
                                $pages = $pages->unique()->sort()->values();
                                $prev = null;
                            @endphp

                            @foreach ($pages as $page)
                                @if ($prev !== null && $page - $prev > 1)
                                    <span class="text-[#9b947c] px-1">···</span>
                                @endif
                                @if ($page == $current)
                                    <span
                                        class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#4d462e] text-[#F2E8C6] font-syne font-bold text-[12px]">
                                        {{ str_pad($page, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                @else
                                    <a href="{{ $barang->url($page) }}"
                                        class="w-10 h-10 flex items-center justify-center rounded-xl border border-[#e0d9c8] text-[#5a584f] font-syne font-bold text-[12px] hover:bg-[#4d462e] hover:text-[#F2E8C6] hover:border-[#4d462e] transition-all">
                                        {{ str_pad($page, 2, '0', STR_PAD_LEFT) }}
                                    </a>
                                @endif
                                @php $prev = $page; @endphp
                            @endforeach

                            @if ($barang->hasMorePages())
                                <a href="{{ $barang->nextPageUrl() }}"
                                    class="w-10 h-10 flex items-center justify-center rounded-xl border border-[#e0d9c8] text-[#5a584f] hover:bg-[#4d462e] hover:text-[#F2E8C6] hover:border-[#4d462e] transition-all">
                                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                                </a>
                            @else
                                <span
                                    class="w-10 h-10 flex items-center justify-center rounded-xl border border-[#e0d9c8] text-[#c8bfa8] cursor-not-allowed">
                                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                                </span>
                            @endif
                        </nav>
                    </div>
                @endif
            @endif

        </section>
    </main>

    @include('user.components.footer')
</body>

</html>
