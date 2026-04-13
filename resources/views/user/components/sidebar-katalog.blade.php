<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>sidebar katalog</title>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
</head>

<body>

    <aside x-show="openFilter || window.innerWidth >= 1024" x-transition @click.away="openFilter = false"
        class="
        fixed lg:sticky
        top-16
        left-0
        z-40
        w-80
        h-[calc(100vh-4rem)]
        bg-surface-container-low
        border-r border-outline-variant/30
        transform lg:translate-x-0
        transition-transform duration-300
    "
        :class="openFilter ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        <form method="GET" action="{{ route('katalog') }}" class="flex flex-col h-full p-6">

            <!-- MOBILE HEADER -->
            <div class="flex justify-between items-center mb-4 lg:hidden">
                <h2 class="text-sm font-bold">Filter</h2>
                <button type="button" @click="openFilter = false">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            @if (request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif

            <!-- HEADER -->
            <div class="flex items-center gap-2 mb-6 flex-shrink-0">
                <span class="material-symbols-outlined text-sm text-primary">filter_alt</span>
                <h2 class="font-label text-xs uppercase tracking-[0.2em] text-outline">
                    Filter Inventaris
                </h2>
            </div>

            <!-- SCROLLABLE FILTER AREA -->
            <div class="flex-1 overflow-y-auto space-y-8 pr-1 scrollbar-thin">

                <!-- KATEGORI -->
                <div x-data="{ selected: {{ json_encode(array_map('strval', (array) request('kategori', []))) }} }">

                    <p class="font-label text-[10px] uppercase tracking-widest text-primary mb-3 opacity-70">
                        Kategori
                    </p>

                    <div class="space-y-2">
                        @foreach ($kategori as $kat)
                            <label class="flex items-center gap-3 p-3 rounded cursor-pointer transition-all relative"
                                :class="selected.includes('{{ $kat->id }}') ?
                                    'bg-[#4d462e]/10 ring-1 ring-[#4d462e]/40' :
                                    'bg-surface-bright hover:bg-surface-container'">

                                <input type="checkbox" name="kategori[]" value="{{ $kat->id }}" x-model="selected"
                                    class="absolute opacity-0 w-5 h-5 cursor-pointer">

                                <div class="w-5 h-5 border flex items-center justify-center transition-all flex-shrink-0 rounded-sm"
                                    :class="selected.includes('{{ $kat->id }}') ?
                                        'bg-[#4d462e] border-[#4d462e]' :
                                        'border-outline bg-transparent'">
                                    <span class="text-white text-xs leading-none"
                                        x-show="selected.includes('{{ $kat->id }}')">
                                        ✔
                                    </span>
                                </div>

                                <div class="flex items-center justify-between w-full">

                                    <div class="flex items-center gap-1">
                                        <span class="font-label text-[11px] uppercase tracking-wider">
                                            {{ $kat->nama }}
                                        </span>

                                        @if ($kat->ikon)
                                            <span
                                                class="w-5 h-5 flex items-center justify-center
                                                {{ in_array($kat->id, $selected ?? []) ? 'text-[#4d462e]' : 'text-primary' }}">
                                                @include('icons.' . $kat->ikon)
                                            </span>
                                        @endif
                                    </div>

                                    <span class="text-[9px] text-outline bg-surface-container px-1.5 py-0.5 rounded">
                                        {{ $kat->barang_aktif_count ?? 0 }}
                                    </span>

                                </div>

                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- RANGE HARGA (TETAP ASLI — ini yang tadi rusak) -->
                <div x-data="{ harga: {{ (int) request('harga', 1000000) }} }">

                    <p class="font-label text-[10px] uppercase tracking-widest text-primary mb-3 opacity-70">
                        Anggaran Sewa (IDR)
                    </p>

                    <div class="bg-surface-dim p-4 rounded border border-outline-variant/30">

                        <input type="range" name="harga" min="50000" max="2500000" step="50000"
                            x-model="harga"
                            class="w-full h-1 bg-outline-variant rounded appearance-none cursor-pointer accent-primary" />

                        <div class="flex justify-between mt-2 text-[9px] text-outline">
                            <span>Rp 50K</span>
                            <span>Rp 2.500K</span>
                        </div>

                        <div class="text-center text-sm mt-2 text-primary font-semibold"
                            x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(harga)">
                        </div>

                    </div>
                </div>

            </div>

            <!-- BUTTONS (TETAP ASLI) -->
            <div class="flex-shrink-0 pt-4 mt-4 border-t border-outline-variant/20 space-y-2">

                <button type="submit"
                    class="w-full bg-primary text-on-primary py-3 rounded text-[10px] uppercase tracking-[0.2em] hover:opacity-90 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    Terapkan Filter
                </button>

                <a href="{{ route('katalog') }}"
                    class="w-full block text-center bg-surface-container-highest text-on-surface-variant py-3 rounded text-[10px] uppercase tracking-[0.2em] border border-outline-variant/30 hover:bg-surface-container flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">restart_alt</span>
                    Reset Filter
                </a>

            </div>

        </form>
    </aside>

    <!-- Alpine JS — letakkan di atas </body>, bukan setelah </html> -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

</body>

</html>
