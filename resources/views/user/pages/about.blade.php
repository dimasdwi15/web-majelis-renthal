<!DOCTYPE html>
<html class="light" lang="id" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 50)">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />

    @vite('resources/css/app.css')
    @vite('resources/js/app.js')

    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">

    <title>Tentang Kami | MAJELIS RENTAL</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&family=Space+Grotesk:wght@500;700&display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap"
        rel="stylesheet" />

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            line-height: 1;
            text-transform: none;
            letter-spacing: normal;
            word-wrap: normal;
            white-space: nowrap;
            direction: ltr;
        }

        body {
            background-color: #faf9f5;
            color: #1b1c1a;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Entrance Animations ── */
        .anim-fade-up {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s cubic-bezier(.22,1,.36,1), transform 0.8s cubic-bezier(.22,1,.36,1);
        }
        .anim-fade-up.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .anim-fade-in {
            opacity: 0;
            transition: opacity 0.9s ease;
        }
        .anim-fade-in.is-visible {
            opacity: 1;
        }

        .anim-slide-left {
            opacity: 0;
            transform: translateX(-50px);
            transition: opacity 0.8s cubic-bezier(.22,1,.36,1), transform 0.8s cubic-bezier(.22,1,.36,1);
        }
        .anim-slide-left.is-visible {
            opacity: 1;
            transform: translateX(0);
        }

        .anim-slide-right {
            opacity: 0;
            transform: translateX(50px);
            transition: opacity 0.8s cubic-bezier(.22,1,.36,1), transform 0.8s cubic-bezier(.22,1,.36,1);
        }
        .anim-slide-right.is-visible {
            opacity: 1;
            transform: translateX(0);
        }

        .anim-scale {
            opacity: 0;
            transform: scale(0.92);
            transition: opacity 0.8s cubic-bezier(.22,1,.36,1), transform 0.8s cubic-bezier(.22,1,.36,1);
        }
        .anim-scale.is-visible {
            opacity: 1;
            transform: scale(1);
        }

        /* Delay utilities */
        .delay-100 { transition-delay: 100ms; }
        .delay-200 { transition-delay: 200ms; }
        .delay-300 { transition-delay: 300ms; }
        .delay-400 { transition-delay: 400ms; }
        .delay-500 { transition-delay: 500ms; }
        .delay-600 { transition-delay: 600ms; }

        /* Hero text animation */
        .hero-title span {
            display: inline-block;
            opacity: 0;
            transform: translateY(100%);
            transition: opacity 0.7s cubic-bezier(.22,1,.36,1), transform 0.7s cubic-bezier(.22,1,.36,1);
        }
        .hero-title.is-visible span {
            opacity: 1;
            transform: translateY(0);
        }
        .hero-title span:nth-child(1) { transition-delay: 300ms; }
        .hero-title span:nth-child(2) { transition-delay: 450ms; }
        .hero-title span:nth-child(3) { transition-delay: 600ms; }

        /* Parallax hero image */
        .hero-img {
            transition: transform 0.1s linear;
        }

        /* Counter animation */
        @keyframes countUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .stat-number { animation: none; }
        .stat-number.animate { animation: countUp 0.6s ease forwards; }

        /* Glitch line decoration */
        .glitch-line::after {
            content: '';
            display: block;
            height: 3px;
            background: #4d462e;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }
        .glitch-line:hover::after { transform: scaleX(1); }

        /* Gear card hover */
        .gear-card {
            transition: transform 0.35s cubic-bezier(.22,1,.36,1), box-shadow 0.35s ease;
        }
        .gear-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 50px rgba(77,70,46,0.15);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #faf9f5; }
        ::-webkit-scrollbar-thumb { background: #4d462e; border-radius: 3px; }

        /* Outdoor tag pill */
        .tag-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #efeeea;
            border: 1px solid #ccc6b9;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.12em;
            color: #4d462e;
            font-family: 'Space Grotesk', sans-serif;
            text-transform: uppercase;
        }
    </style>
</head>

<body class="font-body" x-data="pageAnimations()" x-init="init()">

    @include('user.components.navbar')

    <main class="min-h-screen">

        {{-- ══════════════════════════════════════
             HERO SECTION — Outdoor Rental
        ══════════════════════════════════════ --}}
        <section class="relative h-[100svh] min-h-[600px] max-h-[850px] flex items-end justify-start bg-[#e3e2df] overflow-hidden"
                 x-ref="heroSection"
                 @mousemove="parallax($event)">

            {{-- Background image — outdoor tents / gear / hiking --}}
            <div class="absolute inset-0 z-0">
                <img
                    alt="Outdoor camping and hiking gear setup in nature"
                    class="hero-img w-full h-full object-cover scale-105"
                    x-ref="heroImg"
                    src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1920&q=85"
                    onerror="this.src='https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=1920&q=85'"
                />
            </div>

            {{-- Dark gradient overlay --}}
            <div class="absolute inset-0 bg-gradient-to-t from-[#1b1c1a]/90 via-[#1b1c1a]/30 to-transparent z-10"></div>

            {{-- Noise texture overlay --}}
            <div class="absolute inset-0 z-10 opacity-[0.04]"
                 style="background-image: url(\"data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E\"); background-size: 200px;">
            </div>

            {{-- Top-left tag pills --}}
            <div class="absolute top-8 left-8 md:left-24 z-30 flex gap-3 flex-wrap anim-fade-in" :class="{ 'is-visible': loaded }">
                <span class="tag-pill">
                    <span class="material-symbols-outlined" style="font-size:12px">backpack</span>
                    Camping Gear
                </span>
                <span class="tag-pill">
                    <span class="material-symbols-outlined" style="font-size:12px">landscape</span>
                    Outdoor Equipment
                </span>
                <span class="tag-pill">
                    <span class="material-symbols-outlined" style="font-size:12px">hiking</span>
                    Hiking Essentials
                </span>
            </div>

            {{-- Hero content --}}
            <div class="relative z-20 pb-16 pl-8 md:pl-24 pr-8 w-full">
                <p class="text-[#d0c6a6] uppercase tracking-[0.35em] font-bold text-xs mb-6 font-['Space_Grotesk'] anim-fade-in delay-200"
                   :class="{ 'is-visible': loaded }">
                    ESTABLISHED — BASE CAMP ALPHA, JEMBER
                </p>

                {{-- Word-by-word reveal --}}
                <h1 class="hero-title text-white text-[clamp(3rem,10vw,8rem)] font-black tracking-tighter uppercase leading-[0.9] mb-8"
                    :class="{ 'is-visible': loaded }">
                    <span class="overflow-hidden block">TENTANG</span>
                    <span class="overflow-hidden block text-[#d0c6a6]">KAMI</span>
                </h1>

                {{-- Divider + descriptor --}}
                <div class="flex items-center gap-6 anim-fade-up delay-600" :class="{ 'is-visible': loaded }">
                    <div class="h-px w-16 bg-[#d0c6a6]/50"></div>
                    <p class="text-white/70 text-sm max-w-xs font-['Space_Grotesk'] tracking-wide">
                        Penyedia perlengkapan outdoor terpercaya untuk petualangan tanpa batas.
                    </p>
                </div>

                {{-- Scroll indicator --}}
                <div class="absolute bottom-8 right-8 md:right-24 flex flex-col items-center gap-2 opacity-60">
                    <div class="w-px h-12 bg-white/50 animate-pulse origin-top" style="animation: scrollPulse 2s ease-in-out infinite"></div>
                    <p class="text-white text-[9px] tracking-[0.4em] uppercase font-['Space_Grotesk'] rotate-90 mt-2">SCROLL</p>
                </div>
            </div>
        </section>



        {{-- ══════════════════════════════════════
             PHILOSOPHY SECTION
        ══════════════════════════════════════ --}}
        <section class="bg-[#faf9f5] py-24 px-8 md:px-24" data-observe="philosVisible">
            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-16">
                <div class="lg:col-span-5 anim-slide-left" :class="{ 'is-visible': philosVisible }">
                    <p class="text-[#4d462e] font-bold tracking-widest text-xs mb-4 uppercase font-['Space_Grotesk']">
                        PRINSIP OPERASIONAL
                    </p>
                    <h2 class="text-4xl font-black text-[#1b1c1a] tracking-tighter uppercase mb-8 leading-tight">
                        Filosofi<br /><em class="not-italic text-[#4d462e]">Overbuilt</em><br />for Reliability
                    </h2>
                    <div class="space-y-5 text-[#4a473d] leading-relaxed text-sm">
                        <p>Kami tidak sekadar menyewakan alat. Kami menyediakan sistem pendukung untuk misi paling
                            kritis Anda — dari trekking harian di pegunungan hingga ekspedisi multi-hari di hutan tropis.</p>
                        <p>Setiap unit dalam inventaris Majelis Rental dipilih berdasarkan standar ketahanan lapangan
                            yang melampaui kebutuhan penggunaan biasa. Filosofi "Overbuilt" lahir dari keyakinan bahwa
                            di alam terbuka, kegagalan alat adalah risiko yang tidak boleh terjadi.</p>
                        <p>Kami mengeliminasi variabel tersebut dengan hanya menyediakan gear yang teruji dalam kondisi
                            ekstrem — dari puncak dingin hingga lebah hutan panas.</p>
                    </div>

                    {{-- Animated CTA --}}
                    <div class="mt-10">
                        <a href="{{ url('/katalog') }}"
                           class="group inline-flex items-center gap-3 bg-[#1b1c1a] text-white px-8 py-4 font-['Space_Grotesk'] font-bold text-xs tracking-widest uppercase hover:bg-[#4d462e] transition-colors duration-300">
                            LIHAT SEMUA GEAR
                            <span class="material-symbols-outlined text-sm transition-transform duration-300 group-hover:translate-x-1">arrow_forward</span>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-7 anim-slide-right delay-200" :class="{ 'is-visible': philosVisible }">
                    <div class="grid grid-cols-2 gap-4">
                        {{-- Feature cards --}}
                        <div class="gear-card bg-[#f4f4f0] p-8 flex flex-col justify-between border-t-2 border-transparent hover:border-[#4d462e] transition-colors">
                            <span class="material-symbols-outlined text-4xl text-[#4d462e]">precision_manufacturing</span>
                            <div>
                                <h3 class="font-['Space_Grotesk'] font-bold text-sm tracking-widest text-[#4d462e] uppercase mt-8 mb-2">
                                    PRECISION MILLED
                                </h3>
                                <p class="text-sm text-[#4a473d]">Material grade premium untuk beban kerja tanpa batas di medan apapun.</p>
                            </div>
                        </div>
                        <div class="gear-card bg-[#655e44] p-8 flex flex-col justify-between text-[#F2E8C6]">
                            <span class="material-symbols-outlined text-4xl">verified_user</span>
                            <div>
                                <h3 class="font-['Space_Grotesk'] font-bold text-sm tracking-widest uppercase mt-8 mb-2">
                                    FAIL-SAFE LOGIC
                                </h3>
                                <p class="text-sm opacity-80">Protokol inspeksi 12-titik sebelum setiap pengerahan unit ke lapangan.</p>
                            </div>
                        </div>
                        <div class="gear-card bg-[#efeeea] p-8">
                            <span class="material-symbols-outlined text-4xl text-[#4d462e]">tent</span>
                            <h3 class="font-['Space_Grotesk'] font-bold text-sm tracking-widest text-[#4d462e] uppercase mt-8 mb-2">
                                CAMPING READY
                            </h3>
                            <p class="text-sm text-[#4a473d]">Tenda, sleeping bag, hingga sistem masak portable tersedia lengkap.</p>
                        </div>
                        <div class="gear-card bg-[#1b1c1a] p-8 flex flex-col justify-between text-white">
                            <span class="material-symbols-outlined text-4xl text-[#d0c6a6]">hiking</span>
                            <div>
                                <h3 class="font-['Space_Grotesk'] font-bold text-sm tracking-widest uppercase mt-8 mb-2 text-[#d0c6a6]">
                                    TRAIL TESTED
                                </h3>
                                <p class="text-sm opacity-70">Semua gear diuji langsung di jalur-jalur populer Jawa Timur.</p>
                            </div>
                        </div>

                        {{-- Stats bar --}}
                        <div class="col-span-2 bg-[#efeeea] p-8">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="h-[2px] flex-grow bg-[#ccc6b9]"></div>
                                <p class="font-['Space_Grotesk'] text-[10px] tracking-widest font-bold text-[#4d462e]">FIELD SPECIFICATIONS</p>
                                <div class="h-[2px] flex-grow bg-[#ccc6b9]"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-8">
                                <div class="text-center">
                                    <p class="text-3xl font-black text-[#1b1c1a]">4000+</p>
                                    <p class="font-['Space_Grotesk'] text-[10px] tracking-widest text-[#4a473d] uppercase">RENTAL SELESAI</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-3xl font-black text-[#1b1c1a]">100%</p>
                                    <p class="font-['Space_Grotesk'] text-[10px] tracking-widest text-[#4a473d] uppercase">KEPUASAN</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-3xl font-black text-[#1b1c1a]">24/7</p>
                                    <p class="font-['Space_Grotesk'] text-[10px] tracking-widest text-[#4a473d] uppercase">SUPPORT</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════
             GEAR CATEGORIES — Interactive tabs
        ══════════════════════════════════════ --}}
        <section class="bg-[#f4f4f0] py-24 px-8 md:px-24"
                 x-data="{ activeTab: 0 }"
                 data-observe="gearVisible">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 border-b-2 border-[#ccc6b9] pb-6 gap-6
                            anim-fade-up" :class="{ 'is-visible': gearVisible }">
                    <h2 class="text-5xl font-black tracking-tighter uppercase leading-none text-[#1b1c1a]">
                        HIGH-QUALITY<br />OUTDOOR GEAR
                    </h2>
                    <p class="font-['Space_Grotesk'] text-xs font-bold tracking-widest text-[#4d462e] uppercase max-w-[200px] md:text-right">
                        DIBANGUN UNTUK KONDISI EKSTREM INDONESIA
                    </p>
                </div>

                {{-- Tab navigation --}}
                <div class="flex gap-2 flex-wrap mb-8 anim-fade-up delay-200" :class="{ 'is-visible': gearVisible }">
                    <template x-for="(cat, i) in gearCategories" :key="i">
                        <button
                            @click="activeTab = i"
                            :class="activeTab === i
                                ? 'bg-[#4d462e] text-white'
                                : 'bg-white text-[#4d462e] hover:bg-[#efeeea]'"
                            class="px-6 py-2 font-['Space_Grotesk'] font-bold text-xs tracking-widest uppercase transition-all duration-200 border border-[#ccc6b9]"
                            x-text="cat.label">
                        </button>
                    </template>
                </div>

                {{-- Tab panels --}}
                <div class="anim-scale delay-300" :class="{ 'is-visible': gearVisible }">
                    <template x-for="(cat, i) in gearCategories" :key="i">
                        <div x-show="activeTab === i"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-4"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <template x-for="(item, j) in cat.items" :key="j">
                                <div class="gear-card bg-white p-6 border border-[#efeeea] group cursor-pointer">
                                    <div class="w-12 h-12 rounded-full bg-[#efeeea] flex items-center justify-center mb-6 group-hover:bg-[#4d462e] transition-colors duration-300">
                                        <span class="material-symbols-outlined text-[#4d462e] group-hover:text-white transition-colors duration-300 text-xl" x-text="item.icon"></span>
                                    </div>
                                    <h4 class="font-black text-[#1b1c1a] uppercase tracking-tight mb-2" x-text="item.name"></h4>
                                    <p class="text-sm text-[#4a473d]" x-text="item.desc"></p>
                                    <div class="mt-6 pt-4 border-t border-[#efeeea] flex justify-between items-center">
                                        <span class="font-['Space_Grotesk'] text-[10px] tracking-widest text-[#7b776c] uppercase" x-text="item.spec"></span>
                                        <span class="material-symbols-outlined text-[#4d462e] text-sm opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-2 group-hover:translate-x-0">arrow_forward</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- CTA Banner --}}
                <div class="mt-6 bg-[#4d462e] text-white p-12 flex flex-col md:flex-row items-center justify-between gap-8
                            anim-fade-up delay-400" :class="{ 'is-visible': gearVisible }">
                    <div>
                        <h3 class="text-3xl font-black uppercase tracking-tighter mb-3">Eksplorasi Tanpa Batas</h3>
                        <p class="text-sm opacity-80 max-w-md">Kami menyediakan toolkit lengkap untuk ekspedisi outdoor Anda — dari basecamp hingga puncak.</p>
                    </div>
                    <a href="{{ url('/katalog') }}"
                       class="shrink-0 inline-flex items-center gap-3 bg-white text-[#4d462e] px-8 py-4 font-['Space_Grotesk'] font-bold text-xs tracking-widest uppercase hover:bg-[#ede2c1] transition-colors group">
                        LIHAT KATALOG LENGKAP
                        <span class="material-symbols-outlined text-sm transition-transform duration-300 group-hover:translate-x-1">arrow_forward</span>
                    </a>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════
             HOW IT WORKS — Timeline
        ══════════════════════════════════════ --}}
        <section class="bg-[#1b1c1a] py-24 px-8 md:px-24" data-observe="howVisible">
            <div class="max-w-7xl mx-auto">
                <div class="mb-16 anim-fade-up" :class="{ 'is-visible': howVisible }">
                    <p class="font-['Space_Grotesk'] text-[#d0c6a6] font-bold tracking-widest text-xs mb-4 uppercase">
                        ALUR KERJA
                    </p>
                    <h2 class="text-5xl font-black text-white tracking-tighter uppercase leading-none">
                        CARA KERJA<br /><span class="text-[#d0c6a6]">SISTEM RENTAL</span>
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-0">
                    <template x-for="(step, i) in steps" :key="i">
                        <div class="relative anim-fade-up"
                             :class="{ 'is-visible': howVisible }"
                             :style="`transition-delay: ${i * 150}ms`">
                            {{-- Connector line --}}
                            <div class="hidden md:block absolute top-8 left-1/2 w-full h-px bg-white/10" x-show="i < steps.length - 1"></div>

                            <div class="p-8 border-t border-white/10 group hover:bg-white/5 transition-colors duration-300">
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="w-10 h-10 rounded-full border border-[#4d462e] bg-[#4d462e]/20 flex items-center justify-center shrink-0">
                                        <span class="font-['Space_Grotesk'] font-black text-[#d0c6a6] text-sm" x-text="String(i+1).padStart(2,'0')"></span>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined text-[#d0c6a6] text-3xl mb-4 block" x-text="step.icon"></span>
                                <h4 class="font-black text-white uppercase tracking-tight text-lg mb-3" x-text="step.title"></h4>
                                <p class="text-sm text-white/50 leading-relaxed" x-text="step.desc"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════
             BASECAMP LOCATIONS
        ══════════════════════════════════════ --}}
        <section class="bg-[#faf9f5] py-24 px-8 md:px-24" data-observe="locVisible">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                    <div class="lg:col-span-4 anim-slide-left" :class="{ 'is-visible': locVisible }">
                        <p class="font-['Space_Grotesk'] text-[#4d462e] font-bold tracking-widest text-xs mb-4 uppercase">
                            NETWORK DISTRIBUTION
                        </p>
                        <h2 class="text-5xl font-black text-[#1b1c1a] tracking-tighter uppercase mb-10 leading-none">
                            LOKASI<br />BASECAMP
                        </h2>

                        <div class="space-y-0" x-data="{ activeBase: 0 }">
                            <template x-for="(base, i) in basecamps" :key="i">
                                <button
                                    @click="!base.soon && (activeBase = i)"
                                    :class="{
                                        'opacity-50 cursor-not-allowed': base.soon,
                                        'border-[#4d462e] bg-[#4d462e]/5': activeBase === i && !base.soon,
                                        'border-[#ccc6b9] hover:border-[#4d462e]': activeBase !== i && !base.soon,
                                    }"
                                    class="w-full flex justify-between items-center border-b-2 py-5 text-left transition-all duration-200 group">
                                    <div>
                                        <h4 class="font-black text-lg uppercase tracking-tight" x-text="base.name"></h4>
                                        <p class="font-['Space_Grotesk'] text-[10px] text-[#4a473d] tracking-widest font-bold" x-text="base.sub"></p>
                                    </div>
                                    <span class="material-symbols-outlined text-[#4d462e]" x-text="base.soon ? 'construction' : 'location_on'"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="lg:col-span-8 h-[500px] bg-[#dbdad6] relative overflow-hidden
                                anim-slide-right delay-200" :class="{ 'is-visible': locVisible }">
                        <div class="absolute inset-0 grayscale contrast-125 opacity-40">
                            <img alt="Aerial mountain terrain"
                                class="w-full h-full object-cover"
                                src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1920&q=60"
                                onerror="this.src='https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=1920&q=60'" />
                        </div>

                        {{-- Coordinate card --}}
                        <div class="absolute bottom-8 left-8 right-8 md:right-auto z-10 p-10 bg-[#faf9f5]/95 backdrop-blur-md shadow-2xl md:max-w-sm">
                            <p class="font-['Space_Grotesk'] text-[10px] font-bold text-[#4d462e] tracking-[0.4em] mb-4 uppercase">
                                COORDINATE SYSTEM
                            </p>
                            <div class="space-y-1 mb-4">
                                <p class="text-2xl font-black font-['Space_Grotesk'] tracking-tighter">8.1845° S</p>
                                <p class="text-2xl font-black font-['Space_Grotesk'] tracking-tighter">113.7045° E</p>
                            </div>
                            <p class="text-[10px] text-[#4a473d] mb-6 uppercase tracking-wider font-bold font-['Space_Grotesk']">
                                JEMBER, JAWA TIMUR — Operasional 08:00–21:00
                            </p>
                            <a href="https://maps.google.com/?q=Jember,Jawa+Timur" target="_blank"
                               class="w-full bg-[#4d462e] text-white py-4 px-6 font-['Space_Grotesk'] font-bold text-xs tracking-[0.2em] uppercase hover:bg-[#655e44] transition-colors flex items-center justify-center gap-2 group">
                                <span class="material-symbols-outlined text-sm">map</span>
                                BUKA NAVIGASI
                                <span class="material-symbols-outlined text-sm transition-transform duration-300 group-hover:translate-x-1">open_in_new</span>
                            </a>
                        </div>

                        {{-- Decorative grid --}}
                        <div class="absolute top-8 right-8 opacity-20">
                            <div class="grid grid-cols-4 gap-2">
                                <template x-for="n in 16">
                                    <div class="w-2 h-2 rounded-full bg-[#4d462e]"></div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════
             TESTIMONIALS / TRUST SIGNALS
        ══════════════════════════════════════ --}}
        <section class="bg-[#efeeea] py-24 px-8 md:px-24 overflow-hidden" data-observe="trustVisible">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16 anim-fade-up" :class="{ 'is-visible': trustVisible }">
                    <p class="font-['Space_Grotesk'] text-[#4d462e] font-bold tracking-widest text-xs mb-4 uppercase">
                        DIPERCAYA PARA PETUALANG
                    </p>
                    <h2 class="text-4xl font-black text-[#1b1c1a] tracking-tighter uppercase">
                        KATA MEREKA
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <template x-for="(t, i) in testimonials" :key="i">
                        <div class="anim-fade-up gear-card bg-white p-8 border-t-4 border-[#4d462e]"
                             :class="{ 'is-visible': trustVisible }"
                             :style="`transition-delay: ${i * 150}ms`">
                            <div class="flex gap-1 mb-6">
                                <template x-for="s in 5">
                                    <span class="material-symbols-outlined text-sm text-[#4d462e]" style="font-variation-settings: 'FILL' 1">star</span>
                                </template>
                            </div>
                            <p class="text-[#4a473d] leading-relaxed mb-6 text-sm" x-text="t.text"></p>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-[#4d462e] flex items-center justify-center">
                                    <span class="font-black text-white text-sm" x-text="t.initial"></span>
                                </div>
                                <div>
                                    <p class="font-black text-[#1b1c1a] text-sm uppercase" x-text="t.name"></p>
                                    <p class="font-['Space_Grotesk'] text-[10px] tracking-widest text-[#7b776c] uppercase" x-text="t.role"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

    </main>

    {{-- ══════════════════════════════════════
         FOOTER
    ══════════════════════════════════════ --}}
    <footer class="bg-[#1b1c1a] text-white py-16 px-8 md:px-24">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-start md:items-center gap-12">
            <div>
                <div class="text-3xl font-black tracking-tighter uppercase mb-2">MAJELIS RENTAL</div>
                <p class="font-['Space_Grotesk'] text-xs tracking-widest text-[#d0c6a6] uppercase mb-4">
                    OPERATING OUT OF BASE CAMP ALPHA — JEMBER
                </p>
                <div class="flex gap-3">
                    <span class="tag-pill" style="background:#ffffff10; border-color:#ffffff20; color:#d0c6a6">
                        <span class="material-symbols-outlined" style="font-size:10px">verified</span>
                        TRUSTED SINCE 2020
                    </span>
                </div>
            </div>
            <div class="flex flex-wrap gap-12">
                <div>
                    <h5 class="font-['Space_Grotesk'] text-[10px] font-bold text-[#7b776c] tracking-widest uppercase mb-4">RESOURCES</h5>
                    <ul class="space-y-2 text-sm font-bold uppercase tracking-tight">
                        <li><a class="hover:text-[#d0c6a6] transition-colors glitch-line" href="#">Panduan</a></li>
                        <li><a class="hover:text-[#d0c6a6] transition-colors glitch-line" href="{{ url('/katalog') }}">Inventaris</a></li>
                        <li><a class="hover:text-[#d0c6a6] transition-colors glitch-line" href="#">Kontak</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-['Space_Grotesk'] text-[10px] font-bold text-[#7b776c] tracking-widest uppercase mb-4">LEGAL</h5>
                    <ul class="space-y-2 text-sm font-bold uppercase tracking-tight">
                        <li><a class="hover:text-[#d0c6a6] transition-colors glitch-line" href="#">Ketentuan Layanan</a></li>
                        <li><a class="hover:text-[#d0c6a6] transition-colors glitch-line" href="#">Kebijakan Privasi</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto mt-16 pt-8 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="font-['Space_Grotesk'] text-[8px] tracking-[0.3em] text-white/40 uppercase">
                © 2025 MAJELIS RENTAL LOGISTICS. ALL RIGHTS RESERVED.
            </p>
            <div class="flex gap-4">
                <span class="material-symbols-outlined text-white/40 text-sm">shield</span>
                <span class="material-symbols-outlined text-white/40 text-sm">security</span>
                <span class="material-symbols-outlined text-white/40 text-sm">verified_user</span>
            </div>
        </div>
    </footer>

    {{-- ══════════════════════════════════════
         Alpine.js Data & Scroll Observer
    ══════════════════════════════════════ --}}
    <script>
        function pageAnimations() {
            return {
                loaded: false,
                philosVisible: false,
                gearVisible: false,
                howVisible: false,
                locVisible: false,
                trustVisible: false,

                gearCategories: [
                    {
                        label: 'CAMPING',
                        items: [
                            { icon: 'camping',         name: 'Tenda Camping',     desc: 'Kapasitas 1-8 orang, tahan hujan & angin kencang.',           spec: 'WATERPROOF_SPEC' },
                            { icon: 'bedtime',      name: 'Sleeping Bag',      desc: 'Rating suhu hingga -10°C untuk malam pegunungan.',             spec: 'THERMAL_SPEC' },
                            { icon: 'outdoor_grill',name: 'Kompor Portable',   desc: 'Gas & alkohol stove untuk efisiensi bahan bakar di lapangan.', spec: 'FUEL_SPEC' },
                        ],
                    },
                    {
                        label: 'HIKING',
                        items: [
                            { icon: 'backpack',     name: 'Carrier / Ransel',  desc: '40L–80L dengan frame sistem ergonomis untuk trekking panjang.',spec: 'ERGONOMIC_SPEC' },
                            { icon: 'hiking',       name: 'Trekking Poles',    desc: 'Aluminium ringan dengan grip anti-selip untuk medan berbatu.',  spec: 'LIGHTWEIGHT_SPEC' },
                            { icon: 'flashlight_on',name: 'Headlamp',          desc: 'LED 500 lumen, tahan air IPX6, baterai tahan 40 jam.',         spec: 'LIGHT_SPEC' },
                        ],
                    },
                    {
                        label: 'SAFETY',
                        items: [
                            { icon: 'emergency',    name: 'P3K Kit',           desc: 'Kit lengkap untuk pertolongan pertama di medan terpencil.',    spec: 'MEDICAL_SPEC' },
                            { icon: 'location_on',  name: 'GPS Tracker',       desc: 'Komunikasi satelit untuk daerah tanpa sinyal seluler.',         spec: 'SATELLITE_SPEC' },
                            { icon: 'water_drop',   name: 'Water Filter',      desc: 'Filter portabel untuk air dari sumber alam bebas patogen.',    spec: 'PURIFY_SPEC' },
                        ],
                    },
                ],

                steps: [
                    { icon: 'search',        title: 'Pilih Gear',       desc: 'Telusuri katalog kami dan pilih perlengkapan sesuai rencana ekspedisi Anda.' },
                    { icon: 'calendar_month',title: 'Tentukan Tanggal', desc: 'Atur jadwal pengambilan dan pengembalian sesuai durasi petualangan Anda.' },
                    { icon: 'handshake',     title: 'Konfirmasi',       desc: 'Tim kami memverifikasi ketersediaan dan menyiapkan gear untuk Anda.' },
                    { icon: 'hiking',        title: 'Mulai Petualangan',desc: 'Ambil gear, cek kondisi bersama tim kami, lalu mulailah ekspedisi!' },
                ],

                basecamps: [
                    { name: 'BASECAMP ALPHA', sub: 'JEMBER — HUB PUSAT',          soon: false },
                    { name: 'BASECAMP BRAVO', sub: 'MALANG — DIVISI TEKNIS',       soon: false },
                    { name: 'BASECAMP CHARLIE', sub: 'BANYUWANGI — OUTPOST TIMUR (SOON)', soon: true },
                ],

                testimonials: [
                    {
                        text: 'Tenda yang disewa kondisinya seperti baru. Pemasangan mudah dan tahan hujan deras saat di Semeru. Sangat rekomendasikan!',
                        name: 'BUDI SANTOSO', initial: 'B', role: 'Pendaki — Semeru 2024',
                    },
                    {
                        text: 'Carrier 60L-nya nyaman banget, frame-nya solid. Pelayanan ramah dan prosesnya cepat. Pasti balik lagi sebelum ke Rinjani.',
                        name: 'SARI DEWI', initial: 'S', role: 'Hiker — Rinjani 2024',
                    },
                    {
                        text: 'GPS tracker-nya beneran lifesaver waktu sinyal hilang di hutan. Gear-nya terawat semua, nggak ada yang ngecewain.',
                        name: 'RIZKY PRATAMA', initial: 'R', role: 'Backpacker — Bromo 2023',
                    },
                ],

                init() {
                    // Trigger loaded after paint
                    this.$nextTick(() => {
                        setTimeout(() => { this.loaded = true; }, 80);
                    });

                    // Scroll-based intersection via data-observe attribute
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const key = entry.target.dataset.observe;
                                if (key) this[key] = true;
                                entry.target.querySelectorAll('.anim-fade-up, .anim-fade-in, .anim-slide-left, .anim-slide-right, .anim-scale').forEach(el => {
                                    el.classList.add('is-visible');
                                });
                            }
                        });
                    }, { threshold: 0.08 });

                    document.querySelectorAll('section').forEach(section => observer.observe(section));
                },

                parallax(e) {
                    const img = this.$refs.heroImg;
                    if (!img) return;
                    const { clientX, clientY } = e;
                    const { innerWidth, innerHeight } = window;
                    const x = (clientX / innerWidth - 0.5) * 15;
                    const y = (clientY / innerHeight - 0.5) * 10;
                    img.style.transform = `scale(1.05) translate(${x}px, ${y}px)`;
                },
            };
        }
    </script>

    <style>
        @keyframes scrollPulse {
            0%, 100% { transform: scaleY(1); opacity: 0.6; }
            50%       { transform: scaleY(1.4); opacity: 1; }
        }
    </style>

</body>
</html>
