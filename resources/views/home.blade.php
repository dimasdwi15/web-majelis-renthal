<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Majelis Renthal</title>

    @vite('resources/css/app.css')
    @vite('resources/js/app.js')

    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&family=Bebas+Neue&display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap"
        rel="stylesheet">

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* ── Custom Fonts ───────────────────────────────────────── */
        .font-bebas {
            font-family: 'Bebas Neue', cursive;
        }

        /* ── Grain Overlay ──────────────────────────────────────── */
        .grain::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 1;
        }

        /* ── Hero Animations ────────────────────────────────────── */
        .hero-reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1),
                transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .hero-reveal.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* ── Marquee ────────────────────────────────────────────── */
        .marquee-track {
            display: flex;
            width: max-content;
            animation: marquee 20s linear infinite;
        }

        .marquee-track:hover {
            animation-play-state: paused;
        }

        @keyframes marquee {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(-50%);
            }
        }

        /* ── Scroll Reveal ──────────────────────────────────────── */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(32px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }

        .scroll-reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ── Card Hover Shimmer ─────────────────────────────────── */
        .card-shimmer {
            position: relative;
            overflow: hidden;
        }

        .card-shimmer::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 60%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(242, 232, 198, 0.08), transparent);
            transition: left 0.5s ease;
            z-index: 2;
        }

        .card-shimmer:hover::before {
            left: 150%;
        }

        /* ── Hero Parallax Image ─────────────────────────────────── */
        #hero-img {
            transition: transform 0.1s linear;
            will-change: transform;
        }

        /* ── Number Counter ─────────────────────────────────────── */
        .stat-number {
            display: inline-block;
            font-family: 'Bebas Neue', cursive;
        }
    </style>
</head>

<body class="bg-[#faf9f5] font-inter overflow-x-hidden" x-data="pageController()" x-init="init()">


    @include('user.components.navbar')

    {{-- ══════════════════════════════════════════════════════════
         HERO SECTION
    ══════════════════════════════════════════════════════════ --}}
    <section class="relative h-screen flex items-end pb-16 overflow-hidden grain" x-data="heroParallax()"
        @mousemove="onMouseMove($event)">
        {{-- Background Image with Parallax --}}
        <div class="absolute inset-0 z-0 overflow-hidden">
            <img id="hero-img" alt="Rugged Mountains"
                class="w-full h-[115%] object-cover contrast-[1.08] brightness-[0.78] -top-[10%] absolute left-0"
                src="{{ asset('images/hero.jpg') }}" style="transform: translateY(0px)">
            {{-- Gradient overlays --}}
            <div class="absolute inset-0 bg-gradient-to-t from-[#251D1D]/80 via-[#251D1D]/20 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-[#251D1D]/30 to-transparent"></div>
        </div>

        {{-- Animated corner decoration --}}
        <div class="absolute top-8 right-8 z-10 hidden md:block">
            <div class="border border-[#F2E8C6]/20 rounded-lg p-4 text-right hero-reveal" style="transition-delay:1.2s">
                <p class="text-[#F2E8C6]/40 text-[9px] uppercase tracking-[0.4em] mb-1">Est. 2026</p>
                <p class="text-[#F2E8C6]/60 text-[10px] font-bold uppercase tracking-widest">Jember, Jawa Timur</p>
            </div>
        </div>

        {{-- Scroll indicator --}}
        <div class="absolute bottom-8 right-8 z-10 hidden md:flex flex-col items-center gap-2 hero-reveal"
            style="transition-delay:1.4s">
            <p class="text-[#F2E8C6]/30 text-[9px] uppercase tracking-[0.35em] rotate-90 origin-center mb-6">Scroll</p>
            <div class="w-px h-16 bg-gradient-to-b from-[#F2E8C6]/30 to-transparent"></div>
        </div>

        {{-- Hero Content --}}
        <div class="relative z-10 px-6 md:px-16 max-w-screen-2xl w-full mx-auto">

            {{-- Badge --}}
            <div class="hero-reveal mb-6" style="transition-delay:0.3s">
                <div class="inline-flex items-center gap-3 bg-[#655e44]/80 backdrop-blur-md px-5 py-2 rounded-lg">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <p class="text-[#F2E8C6] text-[10px] font-bold uppercase tracking-[0.35em]">Deploying Excellence
                        Since 2025</p>
                </div>
            </div>

            {{-- Main Title --}}
            <div class="hero-reveal mb-2 overflow-hidden" style="transition-delay:0.5s">
                <h1 class="font-bebas text-[clamp(5rem,14vw,13rem)] text-[#F2E8C6] leading-none tracking-wide">
                    Gear Tangguh
                </h1>
            </div>
            <div class="hero-reveal mb-10 overflow-hidden" style="transition-delay:0.65s">
                <h1 class="font-bebas text-[clamp(5rem,14vw,13rem)] text-transparent leading-none tracking-wide"
                    style="-webkit-text-stroke: 1.5px #F2E8C6;">
                    Tanpa Kompromi
                </h1>
            </div>

            {{-- Bottom Row --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 hero-reveal"
                style="transition-delay:0.85s">
                <button
                    class="group relative bg-[#F2E8C6] text-[#251D1D] px-10 py-4 rounded-lg font-bold uppercase tracking-widest text-sm flex items-center gap-3 overflow-hidden transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-[#655e44]/30"
                    onclick="window.location.href='/katalog'"
                    @mouseenter="$el.querySelector('.btn-bg').style.transform='translateX(0%)'"
                    @mouseleave="$el.querySelector('.btn-bg').style.transform='translateX(-100%)'">
                    <span
                        class="btn-bg absolute inset-0 bg-[#655e44] transition-transform duration-300 -translate-x-full"
                        style="z-index:0"></span>
                    <span class="relative z-10 transition-colors duration-300 group-hover:text-[#F2E8C6]">Explore
                        Repository</span>
                    <span
                        class="material-symbols-outlined relative z-10 text-base transition-all duration-300 group-hover:text-[#F2E8C6] group-hover:translate-x-1">arrow_forward</span>
                </button>

                <div class="flex items-center gap-4">
                    <div class="w-12 h-px bg-[#F2E8C6]/30"></div>
                    <div class="text-left">
                        <p class="text-[#F2E8C6]/40 text-[9px] uppercase tracking-widest">Tersedia</p>
                        <p class="text-[#F2E8C6]/80 text-sm font-bold">7 Kategori Gear</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════
         MARQUEE TICKER
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-[#655e44] py-3 overflow-hidden">
        <div class="marquee-track">
            @foreach (range(1, 2) as $i)
                @foreach (['Tenda Gunung', 'Carrier Bag', 'Sleeping Bag', 'Sepatu Outdoor', 'Kompor Portable', 'Alat Masak', 'Rain Gear', 'Trekking Pole'] as $item)
                    <span
                        class="text-[#F2E8C6]/70 text-[10px] font-bold uppercase tracking-[0.4em] px-8">{{ $item }}</span>
                    <span class="text-[#F2E8C6]/30 px-2">✦</span>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         STATS BAR
    ══════════════════════════════════════════════════════════ --}}
    <section class="bg-[#251D1D] py-12 px-6 scroll-reveal">
        <div
            class="max-w-screen-2xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-0 md:divide-x md:divide-[#F2E8C6]/10">
            @foreach ([['100+', 'Unit Tersedia'], ['500+', 'Penyewaan'], ['5', 'Kategori Gear'], ['4.9', 'Rating Rata-rata']] as $stat)
                <div class="text-center md:px-8">
                    <p class="stat-number text-[#F2E8C6] text-5xl leading-none mb-1">{{ $stat[0] }}</p>
                    <p class="text-[#F2E8C6]/40 text-[10px] uppercase tracking-[0.3em]">{{ $stat[1] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════
         CATEGORIES GRID
    ══════════════════════════════════════════════════════════ --}}
    <section class="bg-[#efeeea] py-24 px-6">
        <div class="max-w-screen-2xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-4 scroll-reveal">
                <div>
                    <p class="text-[10px] font-bold text-[#4d462e]/60 uppercase tracking-[0.4em] mb-3">Technical
                        Categories</p>
                    <h2 class="font-bebas text-7xl md:text-8xl text-[#4d462e] leading-none tracking-wide">Gear Inventory
                    </h2>
                </div>
                <a href="{{ url('/katalog') }}"
                    class="group inline-flex items-center gap-2 text-[#4d462e] text-sm font-bold uppercase tracking-widest border-b border-[#4d462e]/30 pb-1 hover:border-[#4d462e] transition-all">
                    Lihat Semua
                    <span
                        class="material-symbols-outlined text-base group-hover:translate-x-1 transition-transform">north_east</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                @foreach ([
        ['tenda', 'tent', 'Shelter', 'Tenda', 'Shelter teknis untuk kondisi ekstrem. Tahan badai, ringan, dan presisi.', 'https://lh3.googleusercontent.com/aida-public/AB6AXuAOWjRYEbRkozqd1UUBTGciCju1JezTBsfBBrFSdk5hcGAbVr_qxssr9kKvJr9dwxlwrIbiPusfwuZF2FhiJmZttAFzUGafedYkeqKaPwIYEEt_32M6O8Xb0JZ4O7cpk-WRMARPj7afkaSTO_SR7QqzObmmzS8XD8L77v6hog5FmMSYUJXheZS9M5AI4VdlXUbQG2-kBAlmNdkuT5p7qwwshovv-0ZrbJHRxhq5u-YePe7YVvFkavqatosl5YKY80D4s0O6WWw1Jek'],
        ['carrier', 'backpack', 'Load Carriage', 'Tas/Carrier', 'Ergonomi maksimal untuk beban berat. Material cordura mil-spec.', 'https://lh3.googleusercontent.com/aida-public/AB6AXuBTl5M8V1I54ShJS4iEKq6rV6NMStqKqauCFicYWnMIPRuPK11aWTpMuVmoAZOvOBZ0aLKpCUR2b1Wl7tStLJeIPmTQW6BnLhGf1tehZXqmzVU22jATrWUKZb7W-Yza34ooE3vtWW_TEdcR5RlTRn_L9pGOkvdxeQqRURWsMMIDTyKHjnLLrqodlICqugnjdDuz7sknn9nh9UnhEhvdnqr_9ngre1mnSJ93RoCOQd6tvXO59NdK3ckaiFUDFN-yPXA2I2fOs_dqpDc'],
        ['masak', 'cooking', 'Sustainment', 'Alat Masak', 'Sistem pembakaran efisien tinggi untuk logistik di lapangan.', 'https://lh3.googleusercontent.com/aida-public/AB6AXuCVOtftWVts0q5n6HQ7LGvfyUCL4XY1XD584pahguQsYJanQ-KveUJ-gEpo0CmYXVu0r45Y07RVCA13mq_XJ_NnaK--JihkW-CPDu9fKjALjzt8UtYm6OtZUcO5epf0neO1QxVHKpjG3JGjvZqFooXUMKTteeDOqM_eauPaBB1Fit--H4hZ_08qoJ9QMB8AXMPIk_ozLDPJeyAs83YS4Hwh8umfzZstb0xYtO6ohFAaRC11UKKxYSZjENz5VQC2kR7z_cPboHJ3HcQ'],
        ['penerangan', 'flashlight', 'Illumination', 'Penerangan', 'Lumen tinggi, daya tahan baterai ekstrem. Tactical illumination.', 'https://lh3.googleusercontent.com/aida-public/AB6AXuCHnRDKEuplSYeYpe8nLVEe-a-uMnwEfYAk5W71Q2odgWgxYDQK7HySGWp6jq-MaBz_sW33b9aIo5AEnqy6oAP8llcf7RFdz2D2XvZl87m1_3OXZra_ZFIqffOCnrhkWrfYOBLH5pZw5ekAVnd3F6FPQ2pEdQBP60OYr1-GwcpGKAvj68hApoF5HoXw_kt6W_fTNsPNLle47FZZhRHA9tKm1Mf_Fjgmmfa1vZo2-RuDtbYWVZ5NwAP_2KvsO9jmNTXUuG07BllcbUA'],
    ] as $idx => [$slug, $icon, $type, $name, $desc, $img])
                    <a href="{{ url('/katalog?kategori=' . $slug) }}"
                        class="group scroll-reveal card-shimmer bg-white rounded-2xl overflow-hidden border border-transparent hover:border-[#655e44]/20 transition-all duration-500 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#4d462e]/10"
                        style="transition-delay: {{ $idx * 80 }}ms">
                        {{-- Image --}}
                        <div class="overflow-hidden bg-[#f4f4f0] h-52 relative">
                            <img class="w-full h-full object-cover grayscale group-hover:grayscale-0 group-hover:scale-105 transition-all duration-700"
                                alt="{{ $name }}" src="{{ $img }}" />
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            </div>
                            {{-- Type badge --}}
                            <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-md">
                                <p class="text-[9px] font-bold text-[#4d462e] uppercase tracking-widest">
                                    {{ $type }}</p>
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="p-6">
                            <h3
                                class="text-xl font-black text-[#4d462e] uppercase tracking-tight mb-2 group-hover:text-[#655e44] transition-colors">
                                {{ $name }}</h3>
                            <p class="text-[#4a473d]/70 text-sm leading-relaxed mb-5">{{ $desc }}</p>
                            <div class="flex justify-between items-center pt-4 border-t border-[#4d462e]/8">
                                <span class="text-[10px] font-bold text-[#4d462e]/50 uppercase tracking-widest">Lihat
                                    Koleksi</span>
                                <div
                                    class="w-7 h-7 rounded-full bg-[#efeeea] group-hover:bg-[#655e44] flex items-center justify-center transition-all duration-300">
                                    <span
                                        class="material-symbols-outlined text-[#4d462e] group-hover:text-[#F2E8C6] text-sm transition-colors">north_east</span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════
         FEATURED GEAR
    ══════════════════════════════════════════════════════════ --}}
    <section class="bg-[#faf9f5] py-24 px-6">
        <div class="max-w-screen-2xl mx-auto">

            {{-- Header --}}
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-20 gap-8 scroll-reveal">
                <div>
                    <p class="text-[10px] font-bold text-[#4d462e]/50 uppercase tracking-[0.4em] mb-4">Curated
                        Selection</p>
                    <h2 class="font-bebas text-7xl md:text-8xl text-[#4d462e] leading-none tracking-wide">Ready
                        for<br>Deployment</h2>
                </div>
                <div class="bg-[#251D1D] p-6 rounded-xl max-w-xs">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-[#F2E8C6]/50 text-lg mt-0.5">verified</span>
                        <div>
                            <p class="text-[9px] font-bold text-[#F2E8C6]/40 uppercase tracking-widest mb-1">Quality
                                Assured</p>
                            <p class="text-[#F2E8C6]/80 text-sm italic leading-relaxed">"Semua gear melewati inspeksi
                                teknis 24-titik sebelum tersedia."</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cards --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                @foreach ([
        ['Apex-40 Stormbreaker', 'IDR 85K', '/hari', ['40L', 'Weatherproof', '1.2 KG'], 'https://lh3.googleusercontent.com/aida-public/AB6AXuByynQ6ThI2NK811SGz9oFUMFbmZJqcP445VGNQH-juG9vp7NTRCy0HYeiap8twfnYI7VPf7t-cihBg3COBU0wuYziM4UtrWy4fgAxGJ4ZWcAhkIxuGpYmf_ZB-ilKfIwkb8o08UbSQwDkC86hI0QSbUdr7N2EPcamHqE38cBg8GQFogXEJdZWvN7Ut3QKL4pZl_t64AjQmrGFJpBGs4l5b11AO5FFua4RCqIFCNIeUDoFrdy_31kB30_Fu-EAv8d4mnQYpzFH427o'],
        ['Titan-Lume 3000', 'IDR 45K', '/hari', ['3000 Lumens', '24H Runtime', 'Impact-Res'], 'https://lh3.googleusercontent.com/aida-public/AB6AXuBr73ABUJvneXE6c3an66KB5m_aZXd9jrlRjETYnWmUtt7w5R0_3FlKtledAvy36oSVP_0oULKc1J2FghNfWzfWWeN0PI3EZwgujFe8eDl-OxahUhUlD0NKXYx_Ry8y9WGa0KYi7i6svhoNMsFj3VmRKDKfcuzJRC5D-czd7bLkovH6bGTnIYojZFNbXAZu6kz_5yWsroQjUvJESJoaTJEsTjKuK8TNX9gVeyZGdnkIC0d88qjc_zXj8x3aZZzkjOwiXDJxFetxRYY'],
        ['MSR Core-Cook Kit', 'IDR 60K', '/hari', ['Titanium', 'Ultralight', 'Stackable'], 'https://lh3.googleusercontent.com/aida-public/AB6AXuC4SaxYehv6twnTLGPjMrTnhyP_wQsPQoVfDuX_GbFiDrGWRNkH5KdAdtoZKbUBcVbg93hmoSuCT5iA5MHAyU3ZXaIRVVtRtrOczTa-CkZXjlQO85yDqSKIaTvJQ-dkSvUIfICGWFvKjiyw9Ic688kF0f7QVukHTp2yR3974szsaTpjwOljqpGjatUBoSEbxlC1xXbnD81dWOqRqFUZcTl3-m-LS2koQUFOm6LAS-DHGJTSFKDgHVtxq1_BF9Iro0KTBhitSUg0Dnk'],
    ] as $idx => [$name, $price, $unit, $tags, $img])
                    <div class="group scroll-reveal card-shimmer bg-[#f4f4f0] rounded-2xl overflow-hidden hover:shadow-2xl hover:shadow-[#4d462e]/15 transition-all duration-500"
                        style="transition-delay: {{ $idx * 120 }}ms">
                        {{-- Image --}}
                        <div class="relative overflow-hidden h-80">
                            <img class="w-full h-full object-cover grayscale contrast-110 group-hover:grayscale-0 group-hover:scale-105 transition-all duration-700"
                                alt="{{ $name }}" src="{{ $img }}" />
                            {{-- Overlay on hover --}}
                            <div
                                class="absolute inset-0 bg-[#251D1D]/0 group-hover:bg-[#251D1D]/20 transition-all duration-500">
                            </div>

                            {{-- Status badge --}}
                            <div
                                class="absolute top-5 left-5 flex items-center gap-2 bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-lg shadow-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <p class="text-[9px] font-bold text-[#4d462e] uppercase tracking-widest">Tersedia</p>
                            </div>

                            {{-- Price overlay on hover --}}
                            <div
                                class="absolute bottom-5 right-5 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-2 group-hover:translate-y-0">
                                <div class="bg-[#655e44] px-4 py-2 rounded-lg">
                                    <p class="text-[#F2E8C6] font-bold text-sm">{{ $price }}<span
                                            class="text-[#F2E8C6]/60 text-xs font-normal">{{ $unit }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-black text-[#4d462e] uppercase leading-tight tracking-tight">
                                    {{ $name }}</h3>
                                <p class="text-[#4d462e] font-bold text-sm shrink-0 ml-2">{{ $price }}<span
                                        class="text-[#4d462e]/50 text-xs font-normal">{{ $unit }}</span></p>
                            </div>

                            {{-- Tags --}}
                            <div class="flex flex-wrap gap-2 mb-6">
                                @foreach ($tags as $tag)
                                    <span
                                        class="bg-white border border-[#4d462e]/10 px-3 py-1 text-[9px] font-bold text-[#4a473d] uppercase tracking-widest rounded-full">{{ $tag }}</span>
                                @endforeach
                            </div>

                            <a href="{{ url('/katalog') }}"
                                class="group/btn w-full flex items-center justify-center gap-2 bg-[#4d462e] text-[#F2E8C6] py-3.5 rounded-xl font-bold uppercase tracking-widest text-xs hover:bg-[#655e44] transition-all duration-300">
                                Sewa Sekarang
                                <span
                                    class="material-symbols-outlined text-sm group-hover/btn:translate-x-1 transition-transform">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════
         CTA SECTION
    ══════════════════════════════════════════════════════════ --}}
    <section class="bg-[#655e44] py-24 px-6 relative overflow-hidden scroll-reveal">
        {{-- Background pattern --}}
        <div class="absolute inset-0 opacity-5"
            style="background-image: repeating-linear-gradient(45deg, #F2E8C6 0px, #F2E8C6 1px, transparent 0px, transparent 50%); background-size: 24px 24px;">
        </div>

        <div class="relative max-w-screen-2xl mx-auto flex flex-col md:flex-row items-center justify-between gap-10">
            <div>
                <p class="text-[#F2E8C6]/50 text-[10px] font-bold uppercase tracking-[0.4em] mb-4">Ready to Deploy?</p>
                <h2 class="font-bebas text-6xl md:text-7xl text-[#F2E8C6] leading-none tracking-wide">Mulai
                    Petualangan<br>Kamu Sekarang</h2>
            </div>
            <div class="flex flex-col sm:flex-row gap-4 shrink-0">
                <a href="{{ url('/katalog') }}"
                    class="group bg-[#F2E8C6] text-[#251D1D] px-8 py-4 rounded-xl font-bold uppercase tracking-widest text-sm flex items-center gap-2 hover:bg-white transition-all hover:scale-105">
                    Lihat Katalog
                    <span
                        class="material-symbols-outlined text-base group-hover:translate-x-1 transition-transform">north_east</span>
                </a>
                <a href="{{ url('/katalog') }}"
                    class="border border-[#F2E8C6]/30 text-[#F2E8C6] px-8 py-4 rounded-xl font-bold uppercase tracking-widest text-sm hover:bg-[#F2E8C6]/10 transition-all">
                    Hubungi Kami
                </a>
            </div>
        </div>
    </section>


    @include('user.components.footer')

    {{-- ── Alpine & Custom Scripts ── --}}
    <script>
        // ── Page Controller ─────────────────────────────────────
        function pageController() {
            return {
                init() {
                    // Loading screen
                    document.querySelectorAll('.hero-reveal').forEach(el => {
                        setTimeout(() => el.classList.add('show'), 100);
                    });

                    // Scroll reveal observer
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('visible');
                                observer.unobserve(entry.target);
                            }
                        });
                    }, {
                        threshold: 0.12
                    });

                    document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));

                },

            }
        }

        // ── Hero Parallax ────────────────────────────────────────
        function heroParallax() {
            return {
                onMouseMove(e) {
                    const img = document.getElementById('hero-img');
                    if (!img) return;
                    const {
                        innerWidth,
                        innerHeight
                    } = window;
                    const x = (e.clientX / innerWidth - 0.5) * 14;
                    const y = (e.clientY / innerHeight - 0.5) * 10;
                    img.style.transform = `translate(${x}px, ${y}px) scale(1.05)`;
                }
            }
        }
    </script>

</body>

</html>
