@include('user.components.cart')

<nav x-data="{
    mobileOpen: false,
    searchQuery: '{{ request('search') }}'
}"
    class="fixed top-0 left-0 right-0 z-50 bg-[#251D1D]/95 backdrop-blur-xl
           h-16 flex items-center px-6 lg:px-10 shadow-md border-b border-[#655e44]/20">

    <div class="max-w-screen-2xl mx-auto w-full flex items-center justify-between gap-4">

        {{-- Logo --}}
        <a href="{{ url('/') }}" class="flex items-center gap-x-3 group flex-shrink-0">
            <img src="{{ asset('images/majelis.png') }}" alt="Majelis Renthal"
                class="h-9 w-auto object-contain transition-transform group-active:scale-95">
            <div class="hidden sm:block leading-none">
                <span class="font-inter text-lg font-black tracking-tight text-[#F2E8C6]">MAJELIS</span>
                <span class="font-inter text-lg font-black tracking-tight text-[#F2E8C6]/50"> RENTAL</span>
            </div>
        </a>

        {{-- Desktop Menu --}}
        <div class="hidden md:flex items-center gap-x-1">
            <a href="{{ url('/') }}"
                class="px-4 py-2 text-[#F2E8C6]/80 font-semibold text-xs tracking-widest uppercase
                       hover:bg-[#655e44]/40 hover:text-[#F2E8C6] rounded-lg transition-all duration-200
                       {{ request()->is('home') ? 'bg-[#655e44]/40 text-[#F2E8C6]' : '' }}">
                Home
            </a>
            <a href="{{ url('/katalog') }}"
                class="px-4 py-2 text-[#F2E8C6]/80 font-semibold text-xs tracking-widest uppercase
                       hover:bg-[#655e44]/40 hover:text-[#F2E8C6] rounded-lg transition-all duration-200
                       {{ request()->is('katalog*') ? 'bg-[#655e44]/40 text-[#F2E8C6]' : '' }}">
                Katalog
            </a>
            <a href="{{ url('/tentang-kami') }}"
                class="px-4 py-2 text-[#F2E8C6]/80 font-semibold text-xs tracking-widest uppercase
                       hover:bg-[#655e44]/40 hover:text-[#F2E8C6] rounded-lg transition-all duration-200
                       {{ request()->is('tentang-kami') ? 'bg-[#655e44]/40 text-[#F2E8C6]' : '' }}">
                Tentang Kami
            </a>
        </div>

        {{-- Right Section --}}
        <div class="flex items-center gap-x-1 lg:gap-x-2">

            {{-- Search Desktop --}}
            <form method="GET" action="{{ route('katalog') }}" class="relative hidden lg:block"
                @submit.prevent="
                    const url = new URL('{{ route('katalog') }}');
                    if (searchQuery.trim()) url.searchParams.set('search', searchQuery.trim());
                    window.location = url.toString();
                ">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                             text-[#655e44]/50 text-lg pointer-events-none">
                    search
                </span>
                <input type="text" name="search" x-model="searchQuery" placeholder="CARI GEAR..."
                    class="w-60 bg-[#1a1412] border border-[#655e44]/30 pl-10 pr-8 py-2 rounded-lg
                           text-[11px] text-[#F2E8C6] placeholder:text-[#655e44]/40 uppercase tracking-wider
                           focus:outline-none focus:ring-1 focus:ring-[#655e44]/60 focus:border-[#655e44]/60
                           transition-all duration-200">
                <button type="button" x-show="searchQuery.length > 0" @click="searchQuery = ''"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2
                           text-[#F2E8C6]/30 hover:text-[#F2E8C6] transition-colors">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            </form>

            {{-- Cart Button --}}
            <button x-data @click="$store.cart.open = true"
                class="relative flex h-9 w-9 items-center justify-center text-[#F2E8C6]/70
                       hover:text-[#F2E8C6] hover:bg-[#655e44]/40 rounded-lg transition-all duration-200">
                <span class="material-symbols-outlined text-xl">shopping_bag</span>
                <span x-data x-show="$store.cart.count > 0" x-text="$store.cart.count > 9 ? '9+' : $store.cart.count"
                    class="absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-[#a8956a] text-[#251D1D]
                           text-[9px] font-black rounded-full flex items-center justify-center px-1
                           leading-none shadow pointer-events-none">
                </span>
            </button>

            {{-- Notifikasi Bell — hanya untuk user yang sudah login --}}
            @auth
                <div x-data="{ notifOpen: false }" class="relative">
                    <button @click="notifOpen = !notifOpen"
                        class="relative flex h-9 w-9 items-center justify-center text-[#F2E8C6]/70
                           hover:text-[#F2E8C6] hover:bg-[#655e44]/40 rounded-lg transition-all duration-200">
                        <span class="material-symbols-outlined text-xl">notifications</span>
                        {{-- Badge notif belum dibaca --}}
                        @php
                            $unreadNotif = \App\Models\Notifikasi::where('user_id', Auth::id())
                                ->whereNull('dibaca_pada')
                                ->count();
                        @endphp
                        @if ($unreadNotif > 0)
                            <span
                                class="absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-red-500 text-white
                                 text-[9px] font-black rounded-full flex items-center justify-center px-1
                                 leading-none shadow pointer-events-none animate-pulse">
                                {{ $unreadNotif > 9 ? '9+' : $unreadNotif }}
                            </span>
                        @endif
                    </button>

                    {{-- Dropdown Notifikasi --}}
                    <div x-show="notifOpen" @click.away="notifOpen = false"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute right-0 mt-2 w-80 bg-[#251D1D] rounded-lg border border-[#655e44]/30
                           shadow-2xl z-50 overflow-hidden">

                        <div class="flex items-center justify-between px-4 py-3 border-b border-[#655e44]/20">
                            <span class="text-[#F2E8C6] text-xs font-black uppercase tracking-widest">Notifikasi</span>
                            <a href="{{ route('user.notifikasi.index') }}"
                                class="text-[#a8956a] text-[10px] font-semibold uppercase tracking-wider hover:text-[#F2E8C6] transition-colors">
                                Lihat Semua
                            </a>
                        </div>

                        @php
                            $notifTerbaru = \App\Models\Notifikasi::where('user_id', Auth::id())
                                ->latest()
                                ->take(4)
                                ->get();
                        @endphp

                        @foreach ($notifTerbaru as $notif)
                            <a href="{{ route('user.notifikasi.index') }}"
                                class="flex items-start gap-3 px-4 py-3 hover:bg-[#655e44]/20 transition-colors
           border-b border-[#655e44]/10 last:border-0
           {{ is_null($notif->dibaca_pada) ? 'bg-[#655e44]/10' : '' }}">

                                <span
                                    class="material-symbols-outlined text-base mt-0.5 flex-shrink-0
                 {{ $notif->tipe === 'denda' ? 'text-red-400' : 'text-[#a8956a]' }}">
                                    {{ $notif->tipe === 'denda' ? 'warning' : 'info' }}
                                </span>

                                <div class="flex-1 min-w-0">
                                    <p class="text-[#F2E8C6] text-[11px] font-semibold leading-snug truncate">
                                        {{ $notif->judul }}
                                    </p>
                                    <p class="text-[#F2E8C6]/50 text-[10px] mt-0.5 line-clamp-2">
                                        {{ $notif->pesan }}
                                    </p>
                                    <p class="text-[#655e44] text-[9px] mt-1">
                                        {{ $notif->created_at->diffForHumans() }}
                                    </p>
                                </div>

                                @if (is_null($notif->dibaca_pada))
                                    <span class="w-2 h-2 bg-red-500 rounded-full flex-shrink-0 mt-1"></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endauth

            {{-- Account --}}
            <div x-data="{ accOpen: false }" class="relative">
                <button @click="accOpen = !accOpen"
                    class="flex h-9 w-9 items-center justify-center text-[#F2E8C6]/70
                           hover:text-[#F2E8C6] hover:bg-[#655e44]/40 rounded-lg transition-all duration-200">
                    <span class="material-symbols-outlined text-2xl">account_circle</span>
                </button>

                <div x-show="accOpen" @click.away="accOpen = false"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute right-0 mt-2 w-56 bg-[#251D1D] rounded-lg border border-[#655e44]/30
                           shadow-2xl z-50 overflow-hidden">

                    @guest
                        <a href="{{ route('login') }}"
                            class="flex items-center gap-3 px-4 py-3 text-[#F2E8C6]/80 text-xs font-semibold
                                   uppercase tracking-wider hover:bg-[#655e44]/40 hover:text-[#F2E8C6] transition-all">
                            <span class="material-symbols-outlined text-base">login</span>
                            Login
                        </a>
                        <a href="{{ route('register') }}"
                            class="flex items-center gap-3 px-4 py-3 text-[#F2E8C6]/80 text-xs font-semibold
                                   uppercase tracking-wider hover:bg-[#655e44]/40 hover:text-[#F2E8C6] transition-all
                                   border-t border-[#655e44]/20">
                            <span class="material-symbols-outlined text-base">person_add</span>
                            Register
                        </a>
                    @endguest

                    @auth
                        {{-- Info User --}}
                        <div class="px-4 py-3 border-b border-[#655e44]/20">
                            <p class="text-[#F2E8C6]/40 text-[9px] uppercase tracking-widest">Masuk sebagai</p>
                            <p class="text-[#F2E8C6] text-xs font-semibold truncate mt-0.5">
                                {{ Auth::user()->name }}
                            </p>
                            <p class="text-[#655e44]/70 text-[10px] truncate">{{ Auth::user()->email }}</p>
                        </div>

                        {{-- Menu Akun --}}
                        <a href="{{ route('user.dashboard') }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-[#F2E8C6]/80 text-xs font-semibold
                                   uppercase tracking-wider hover:bg-[#655e44]/40 hover:text-[#F2E8C6] transition-all
                                   {{ request()->routeIs('user.dashboard') ? 'bg-[#655e44]/30 text-[#F2E8C6]' : '' }}">
                            <span class="material-symbols-outlined text-base">dashboard</span>
                            Dashboard
                        </a>

                        <a href="{{ route('user.pesanan.index') }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-[#F2E8C6]/80 text-xs font-semibold
                                   uppercase tracking-wider hover:bg-[#655e44]/40 hover:text-[#F2E8C6] transition-all
                                   {{ request()->routeIs('user.pesanan.*') ? 'bg-[#655e44]/30 text-[#F2E8C6]' : '' }}">
                            <span class="material-symbols-outlined text-base">receipt_long</span>
                            Pesanan Saya
                            @php
                                $pesananMenunggu = \App\Models\Transaksi::where('user_id', Auth::id())
                                    ->where('status', 'menunggu_pembayaran')
                                    ->count();
                            @endphp
                            @if ($pesananMenunggu > 0)
                                <span
                                    class="ml-auto bg-[#a8956a] text-[#251D1D] text-[9px] font-black
                                         rounded-full min-w-[16px] h-4 flex items-center justify-center px-1">
                                    {{ $pesananMenunggu }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('user.notifikasi.index') }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-[#F2E8C6]/80 text-xs font-semibold
                                   uppercase tracking-wider hover:bg-[#655e44]/40 hover:text-[#F2E8C6] transition-all
                                   {{ request()->routeIs('user.notifikasi.*') ? 'bg-[#655e44]/30 text-[#F2E8C6]' : '' }}">
                            <span class="material-symbols-outlined text-base">notifications</span>
                            Notifikasi
                            @if (isset($unreadNotif) && $unreadNotif > 0)
                                <span
                                    class="ml-auto bg-red-500 text-white text-[9px] font-black
                                         rounded-full min-w-[16px] h-4 flex items-center justify-center px-1">
                                    {{ $unreadNotif }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('user.profil.edit') }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-[#F2E8C6]/80 text-xs font-semibold
                                   uppercase tracking-wider hover:bg-[#655e44]/40 hover:text-[#F2E8C6] transition-all
                                   border-b border-[#655e44]/20
                                   {{ request()->routeIs('user.profil.*') ? 'bg-[#655e44]/30 text-[#F2E8C6]' : '' }}">
                            <span class="material-symbols-outlined text-base">manage_accounts</span>
                            Edit Profil
                        </a>

                        {{-- Logout --}}
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-3 px-4 py-3 text-red-400/80 text-xs
                                       font-semibold uppercase tracking-wider hover:bg-red-900/30
                                       hover:text-red-400 transition-all">
                                <span class="material-symbols-outlined text-base">logout</span>
                                Logout
                            </button>
                        </form>
                    @endauth
                </div>
            </div>

            {{-- Mobile Hamburger --}}
            <button @click="mobileOpen = !mobileOpen"
                class="md:hidden flex h-9 w-9 items-center justify-center text-[#F2E8C6]/70
                       hover:text-[#F2E8C6] hover:bg-[#655e44]/40 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-xl transition-transform duration-200"
                    :class="mobileOpen ? 'rotate-90' : ''">
                    menu
                </span>
            </button>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div x-show="mobileOpen" @click.away="mobileOpen = false" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="md:hidden absolute top-16 inset-x-0 bg-[#1a1412] border-b border-[#655e44]/30 z-40 shadow-xl">

        <div class="px-4 pt-4 pb-2">
            <form method="GET" action="{{ route('katalog') }}"
                @submit.prevent="
                    const url = new URL('{{ route('katalog') }}');
                    if (searchQuery.trim()) url.searchParams.set('search', searchQuery.trim());
                    mobileOpen = false;
                    window.location = url.toString();
                ">
                <div class="relative">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                                 text-[#655e44]/50 text-lg pointer-events-none">
                        search
                    </span>
                    <input type="text" x-model="searchQuery" placeholder="Cari gear..."
                        class="w-full bg-[#251D1D] border border-[#655e44]/30 pl-10 pr-4 py-2.5 rounded-lg
                               text-xs text-[#F2E8C6] placeholder:text-[#655e44]/40
                               focus:outline-none focus:ring-1 focus:ring-[#655e44]/60">
                </div>
            </form>
        </div>

        {{-- Mobile Nav Links --}}
        <nav class="px-4 pb-2 space-y-1 mt-1">
            <a href="{{ url('/home') }}" @click="mobileOpen = false"
                class="flex items-center gap-3 px-4 py-3 text-[#F2E8C6]/80 text-xs font-semibold
                       uppercase tracking-widest hover:bg-[#655e44]/40 hover:text-[#F2E8C6]
                       rounded-lg transition-colors
                       {{ request()->is('home') ? 'bg-[#655e44]/40 text-[#F2E8C6]' : '' }}">
                <span class="material-symbols-outlined text-base">home</span>
                Home
            </a>
            <a href="{{ url('/katalog') }}" @click="mobileOpen = false"
                class="flex items-center gap-3 px-4 py-3 text-[#F2E8C6]/80 text-xs font-semibold
                       uppercase tracking-widest hover:bg-[#655e44]/40 hover:text-[#F2E8C6]
                       rounded-lg transition-colors
                       {{ request()->is('katalog*') ? 'bg-[#655e44]/40 text-[#F2E8C6]' : '' }}">
                <span class="material-symbols-outlined text-base">inventory_2</span>
                Katalog
            </a>
            <a href="{{ url('/tentang-kami') }}" @click="mobileOpen = false"
                class="flex items-center gap-3 px-4 py-3 text-[#F2E8C6]/80 text-xs font-semibold
                       uppercase tracking-widest hover:bg-[#655e44]/40 hover:text-[#F2E8C6]
                       rounded-lg transition-colors
                       {{ request()->is('tentang-kami') ? 'bg-[#655e44]/40 text-[#F2E8C6]' : '' }}">
                <span class="material-symbols-outlined text-base">groups</span>
                Tentang Kami
            </a>
        </nav>

        {{-- Mobile Akun Section (hanya jika sudah login) --}}
        @auth
            <div class="px-4 pb-4 mt-1 border-t border-[#655e44]/20 pt-3">
                <p class="text-[#655e44]/60 text-[9px] uppercase tracking-widest px-4 mb-1">Akun Saya</p>
                <a href="{{ route('user.dashboard') }}" @click="mobileOpen = false"
                    class="flex items-center gap-3 px-4 py-3 text-[#F2E8C6]/80 text-xs font-semibold
                       uppercase tracking-widest hover:bg-[#655e44]/40 hover:text-[#F2E8C6]
                       rounded-lg transition-colors
                       {{ request()->routeIs('user.dashboard') ? 'bg-[#655e44]/40 text-[#F2E8C6]' : '' }}">
                    <span class="material-symbols-outlined text-base">dashboard</span>
                    Dashboard
                </a>
                <a href="{{ route('user.pesanan.index') }}" @click="mobileOpen = false"
                    class="flex items-center gap-3 px-4 py-3 text-[#F2E8C6]/80 text-xs font-semibold
                       uppercase tracking-widest hover:bg-[#655e44]/40 hover:text-[#F2E8C6]
                       rounded-lg transition-colors
                       {{ request()->routeIs('user.pesanan.*') ? 'bg-[#655e44]/40 text-[#F2E8C6]' : '' }}">
                    <span class="material-symbols-outlined text-base">receipt_long</span>
                    Pesanan Saya
                </a>
                <a href="{{ route('user.notifikasi.index') }}" @click="mobileOpen = false"
                    class="flex items-center gap-3 px-4 py-3 text-[#F2E8C6]/80 text-xs font-semibold
                       uppercase tracking-widest hover:bg-[#655e44]/40 hover:text-[#F2E8C6]
                       rounded-lg transition-colors
                       {{ request()->routeIs('user.notifikasi.*') ? 'bg-[#655e44]/40 text-[#F2E8C6]' : '' }}">
                    <span class="material-symbols-outlined text-base">notifications</span>
                    Notifikasi
                    @if (isset($unreadNotif) && $unreadNotif > 0)
                        <span
                            class="ml-auto bg-red-500 text-white text-[9px] font-black
                             rounded-full min-w-[16px] h-4 flex items-center justify-center px-1">
                            {{ $unreadNotif }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('user.profil.edit') }}" @click="mobileOpen = false"
                    class="flex items-center gap-3 px-4 py-3 text-[#F2E8C6]/80 text-xs font-semibold
                       uppercase tracking-widest hover:bg-[#655e44]/40 hover:text-[#F2E8C6]
                       rounded-lg transition-colors
                       {{ request()->routeIs('user.profil.*') ? 'bg-[#655e44]/40 text-[#F2E8C6]' : '' }}">
                    <span class="material-symbols-outlined text-base">manage_accounts</span>
                    Edit Profil
                </a>
            </div>
        @endauth
    </div>
</nav>

{{-- Spacer agar konten tidak tertutup navbar fixed --}}
<div class="h-16"></div>
