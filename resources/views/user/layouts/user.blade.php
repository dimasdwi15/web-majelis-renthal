<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Akun') | MAJELIS RENTAL</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0&display=swap" rel="stylesheet" />

    {{-- Midtrans Snap --}}
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>

    <style>
        :root {
            --bg:       #f0ede6;
            --surface:  #faf9f5;
            --card:     #ffffff;
            --card2:    #f4f2ec;
            --sidebar:  #251D1D;
            --primary:  #655e44;
            --primary-lt: #a8956a;
            --cream:    #F2E8C6;
            --text:     #2f342e;
            --text-md:  #4d4a3e;
            --text-muted: #7b776c;
            --border:   rgba(101, 94, 68, 0.14);
            --border-md: rgba(101, 94, 68, 0.28);
            --shadow:   0 2px 10px rgba(37, 29, 29, 0.07);
            --shadow-lg: 0 8px 32px rgba(37, 29, 29, 0.10);
        }

        * { box-sizing: border-box; }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 20;
            display: inline-block;
            line-height: 1;
            vertical-align: middle;
        }

        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 2px; }

        @keyframes pulse-ring {
            0%   { box-shadow: 0 0 0 0 currentColor; opacity: 0.7; }
            70%  { box-shadow: 0 0 0 5px currentColor; opacity: 0; }
            100% { box-shadow: 0 0 0 0 currentColor; opacity: 0; }
        }
        .pulse-live { animation: pulse-ring 1.8s cubic-bezier(0.66, 0, 0, 1) infinite; }

        .toast-enter { animation: toastIn 0.3s ease; }
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Sidebar nav states */
        .nav-active {
            background: rgba(242, 232, 198, 0.10) !important;
            color: #F2E8C6 !important;
        }
        .nav-active .nav-icon { color: #a8956a !important; }

        .nav-item {
            color: #9a8f7c;
            transition: all 0.15s ease;
        }
        .nav-item:hover {
            background: rgba(255,255,255,0.06);
            color: #F2E8C6;
        }
        .nav-item:hover .nav-icon { color: #a8956a; }

        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

        /* Sidebar accent line */
        .nav-active-bar {
            position: relative;
        }
        .nav-active-bar::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: #a8956a;
            border-radius: 0 2px 2px 0;
        }
    </style>
</head>

<body x-data="{ sidebarOpen: false }">

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden"
         style="display:none;"></div>

    <div class="min-h-screen flex">

        {{-- ===== SIDEBAR ===== --}}
        <aside class="fixed top-0 left-0 h-full w-64 z-50 flex flex-col bg-[#1e1714] transition-transform duration-300 -translate-x-full lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               style="border-right: 1px solid rgba(255,255,255,0.04);">

            {{-- Logo --}}
            <div class="flex items-center justify-between px-5 py-5 flex-shrink-0" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <a href="{{ url('/') }}" class="group flex items-center gap-2.5">
                    <div class="w-8 h-8 flex items-center justify-center rounded-lg" style="background: linear-gradient(135deg, #655e44, #4d4030); border: 1px solid rgba(168,149,106,0.3);">
                        <span class="material-symbols-outlined text-[#F2E8C6] text-lg">tent</span>
                    </div>
                    <div>
                        <p class="text-[#F2E8C6] text-sm font-black tracking-tight leading-none">MAJELIS</p>
                        <p class="text-[10px] font-bold tracking-[0.25em] uppercase" style="color: #655e44;">RENTHAL</p>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden transition-colors" style="color: #655e44;">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            {{-- User Info --}}
            <div class="px-4 py-4 flex-shrink-0 mx-3 my-3 rounded-xl" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.05);">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background: linear-gradient(135deg, #655e44, #4d4030);">
                        <span class="text-[#F2E8C6] text-sm font-black">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[#F2E8C6] text-xs font-bold truncate">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] truncate" style="color: #655e44;">{{ Auth::user()->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 space-y-0.5 overflow-y-auto scrollbar-hide pb-2">
                @php
                    $navItems = [
                        ['route' => 'user.dashboard',        'icon' => 'space_dashboard',  'label' => 'Dashboard'],
                        ['route' => 'user.pesanan.index',    'icon' => 'receipt_long',     'label' => 'Pesanan Saya'],
                        ['route' => 'user.notifikasi.index', 'icon' => 'notifications',    'label' => 'Notifikasi'],
                        ['route' => 'user.profil.edit',      'icon' => 'manage_accounts',  'label' => 'Edit Profil'],
                    ];
                @endphp

                <p class="text-[9px] font-black uppercase tracking-[0.2em] px-3 pt-3 pb-1.5" style="color: #3d3530;">Menu</p>

                @foreach ($navItems as $item)
                    @php
                        $isActive = request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*');
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl group relative transition-all duration-150
                              {{ $isActive ? 'nav-active nav-active-bar' : 'nav-item' }}">
                        <span class="material-symbols-outlined nav-icon text-lg flex-shrink-0 transition-colors">
                            {{ $item['icon'] }}
                        </span>
                        <span class="text-xs font-bold uppercase tracking-wider flex-1">{{ $item['label'] }}</span>
                        @if ($item['route'] === 'user.notifikasi.index')
                            @php
                                $unreadCount = \App\Models\Notifikasi::where('user_id', Auth::id())->where('dibaca', false)->count();
                            @endphp
                            @if ($unreadCount > 0)
                                <span class="min-w-[18px] h-[18px] text-[#251D1D] text-[9px] font-black rounded-full flex items-center justify-center px-1"
                                      style="background: #a8956a;">
                                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                </span>
                            @endif
                        @endif
                    </a>
                @endforeach

                <p class="text-[9px] font-black uppercase tracking-[0.2em] px-3 pt-5 pb-1.5" style="color: #3d3530;">Lainnya</p>

                <a href="{{ url('/') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl nav-item group">
                    <span class="material-symbols-outlined nav-icon text-lg flex-shrink-0 transition-colors">public</span>
                    <span class="text-xs font-bold uppercase tracking-wider">Website Utama</span>
                </a>
                <a href="{{ url('/katalog') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl nav-item group">
                    <span class="material-symbols-outlined nav-icon text-lg flex-shrink-0 transition-colors">explore</span>
                    <span class="text-xs font-bold uppercase tracking-wider">Katalog</span>
                </a>
            </nav>

            {{-- Logout --}}
            <div class="px-3 pb-5 flex-shrink-0" style="border-top: 1px solid rgba(255,255,255,0.05);">
                <div class="pt-3">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all group"
                                style="color: #7b6e5e;"
                                onmouseover="this.style.background='rgba(220,38,38,0.12)'; this.style.color='#f87171';"
                                onmouseout="this.style.background=''; this.style.color='#7b6e5e';">
                            <span class="material-symbols-outlined text-lg flex-shrink-0">logout</span>
                            <span class="text-xs font-bold uppercase tracking-wider">Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- ===== MAIN CONTENT ===== --}}
        <div class="flex-1 lg:ml-64 min-w-0 flex flex-col min-h-screen">

            {{-- Mobile Top Bar --}}
            <header class="sticky top-0 z-30 flex items-center justify-between px-4 py-3 lg:hidden"
                    style="background: #1e1714; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <button @click="sidebarOpen = true" class="transition-colors" style="color: #7b776c;">
                    <span class="material-symbols-outlined text-xl">menu</span>
                </button>
                <p class="text-[#F2E8C6] text-sm font-black tracking-tight">MAJELIS RENTAL</p>
                <a href="{{ route('user.notifikasi.index') }}" class="relative transition-colors" style="color: #7b776c;">
                    <span class="material-symbols-outlined text-xl">notifications</span>
                    @php $unreadCount = \App\Models\Notifikasi::where('user_id', Auth::id())->where('dibaca', false)->count(); @endphp
                    @if ($unreadCount > 0)
                        <span class="absolute -top-1 -right-1 w-4 h-4 text-[#251D1D] text-[8px] font-black rounded-full flex items-center justify-center"
                              style="background: #a8956a;">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                    @endif
                </a>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 px-4 py-6 lg:px-8 lg:py-8 max-w-5xl w-full mx-auto">

                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-2"
                         class="flex items-center gap-3 text-xs font-semibold px-4 py-3 rounded-xl mb-5 toast-enter"
                         style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;">
                        <span class="material-symbols-outlined text-base flex-shrink-0">check_circle</span>
                        {{ session('success') }}
                        <button @click="show = false" class="ml-auto transition-colors hover:opacity-60">
                            <span class="material-symbols-outlined text-base">close</span>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-2"
                         class="flex items-center gap-3 text-xs font-semibold px-4 py-3 rounded-xl mb-5 toast-enter"
                         style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;">
                        <span class="material-symbols-outlined text-base flex-shrink-0">error</span>
                        {{ session('error') }}
                        <button @click="show = false" class="ml-auto transition-colors hover:opacity-60">
                            <span class="material-symbols-outlined text-base">close</span>
                        </button>
                    </div>
                @endif

                @yield('content')
            </main>

            {{-- Footer --}}
            <footer class="px-8 py-4 mt-auto" style="border-top: 1px solid rgba(101,94,68,0.1); background: #faf9f5;">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-center" style="color: #a09880;">
                    © {{ date('Y') }} Majelis Rental — All rights reserved
                </p>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
