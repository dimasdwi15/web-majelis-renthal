<!DOCTYPE html>
<html class="light" lang="id">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />

    {{-- FIX: Hapus script tailwind.config — itu hanya untuk CDN Tailwind yg tidak di-load --}}
    {{-- Gunakan Vite + theme.css, sama seperti katalog.blade.php --}}
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')

    {{-- FIX: Tambah theme.css agar custom color classes bekerja --}}
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">

    <title>Tentang Kami | MAJELIS RENTAL</title>

    {{-- Fonts — gabungkan dalam satu request --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&family=Space+Grotesk:wght@500;700&display=swap"
        rel="stylesheet" />

    {{-- FIX: Material Symbols harus ada sebagai tag terpisah agar benar di-load --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap"
        rel="stylesheet" />

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
    </style>
</head>

<body class="font-body">

    @include('user.components.navbar')

    <main class="min-h-screen">

        <!-- Hero Section: TENTANG KAMI -->
        <section class="relative h-[614px] flex items-center justify-center bg-[#e3e2df] overflow-hidden">
            <div class="absolute inset-0 bg-[#4d462e]/20 z-10"></div>
            <img alt="Rugged mountain landscape"
                class="absolute inset-0 w-full h-full object-cover"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuCdSJbapKRkCiReOk4971sTfisHPmPzyuRiMp1ln0yKo4G2AQ5mEEyu_gKVXbhCDkd3g2JyOyZ3Hk26sD7gEeBxaISWfVXu_ks3lvCZ5xfz60_0OWVpqdEhHAd2XYqlEu_WGuigu_3p2E9AtKOzpPgwQqqSn3In8lzQgoZy3h5MJnutZIYb1FJ-mAc43jnS8MaVyBOTzKQ2EpFOEyPhdLd1vH92sN7-gLwiEs7Knaef-P8jTqOjdFZM4vOt6G5vIf7zLE2U_a1Na7Q" />
            <div class="relative z-20 text-center px-4">
                <p class="text-white uppercase tracking-[0.3em] font-bold text-sm mb-4 font-['Space_Grotesk']">
                    ESTABLISHED IN BASE CAMP ALPHA
                </p>
                <h1 class="text-white text-6xl md:text-8xl font-black tracking-tighter uppercase leading-none">
                    TENTANG KAMI
                </h1>
            </div>
        </section>

        <!-- Philosophy Section -->
        <section class="bg-[#faf9f5] py-24 px-8 md:px-24">
            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12">
                <div class="lg:col-span-5">
                    <p class="text-[#4d462e] font-bold tracking-widest text-xs mb-4 uppercase font-['Space_Grotesk']">
                        PRINSIP OPERASIONAL
                    </p>
                    <h2 class="text-4xl font-black text-[#1b1c1a] tracking-tighter uppercase mb-8 leading-tight">
                        Filosofi Overbuilt for Reliability
                    </h2>
                    <div class="space-y-6 text-[#4a473d] leading-relaxed">
                        <p>Kami tidak sekadar menyewakan alat. Kami menyediakan sistem pendukung untuk misi paling
                            kritis Anda. Setiap unit dalam inventaris Majelis Rental dipilih berdasarkan standar
                            ketahanan industri yang melampaui kebutuhan penggunaan sipil biasa.</p>
                        <p>Filosofi "Overbuilt" lahir dari pengalaman lapangan di mana kegagalan alat bukan sekadar
                            ketidaknyamanan, melainkan risiko. Kami mengeliminasi variabel tersebut dengan hanya
                            menyediakan gear yang teruji dalam kondisi ekstrem.</p>
                    </div>
                </div>
                <div class="lg:col-span-7 grid grid-cols-2 gap-4">
                    <div class="bg-[#f4f4f0] p-8 flex flex-col justify-between">
                        <span class="material-symbols-outlined text-4xl text-[#4d462e]">precision_manufacturing</span>
                        <div>
                            <h3 class="font-['Space_Grotesk'] font-bold text-sm tracking-widest text-[#4d462e] uppercase mt-8 mb-2">
                                PRECISION MILLED
                            </h3>
                            <p class="text-sm text-[#4a473d]">Material grade dirgantara untuk beban kerja tanpa batas.</p>
                        </div>
                    </div>
                    <div class="bg-[#655e44] p-8 flex flex-col justify-between text-[#F2E8C6]">
                        <span class="material-symbols-outlined text-4xl">verified_user</span>
                        <div>
                            <h3 class="font-['Space_Grotesk'] font-bold text-sm tracking-widest uppercase mt-8 mb-2">
                                FAIL-SAFE LOGIC
                            </h3>
                            <p class="text-sm opacity-80">Protokol inspeksi 12-titik sebelum setiap pengerahan unit.</p>
                        </div>
                    </div>
                    <div class="bg-[#efeeea] p-8 col-span-2">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="h-[2px] flex-grow bg-[#ccc6b9]"></div>
                            <p class="font-['Space_Grotesk'] text-xs tracking-widest font-bold text-[#4d462e]">
                                FIELD SPECIFICATIONS
                            </p>
                            <div class="h-[2px] flex-grow bg-[#ccc6b9]"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-8">
                            <div class="text-center">
                                <p class="text-2xl font-black text-[#1b1c1a]">4000+</p>
                                <p class="font-['Space_Grotesk'] text-[10px] tracking-widest text-[#4a473d] uppercase">DEPLOYS</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-black text-[#1b1c1a]">100%</p>
                                <p class="font-['Space_Grotesk'] text-[10px] tracking-widest text-[#4a473d] uppercase">RELIABILITY</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-black text-[#1b1c1a]">24/7</p>
                                <p class="font-['Space_Grotesk'] text-[10px] tracking-widest text-[#4a473d] uppercase">SUPPORT</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Industrial Gear Section -->
        <section class="bg-[#f4f4f0] py-24 px-8 md:px-24">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-end justify-between mb-12 border-b-2 border-[#ccc6b9] pb-6">
                    <h2 class="text-5xl font-black tracking-tighter uppercase leading-none text-[#1b1c1a]">
                        HIGH-QUALITY<br />INDUSTRIAL GEAR
                    </h2>
                    <p class="font-['Space_Grotesk'] text-xs font-bold tracking-widest text-[#4d462e] uppercase max-w-[200px] text-right">
                        DIBANGUN UNTUK KONDISI EKSTREM
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2 md:row-span-2 relative group overflow-hidden bg-[#e3e2df]">
                        <img alt="Tactical equipment"
                            class="w-full h-full object-cover"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuCHTn2RJTjN7HS0zu1kMTUrssvKEoaAWmPCwhh1uRldjnEl9lYTjNLdpBcm5rgOAwqLWoysEcYLJGPMAyPUnBQHEdwCOw6Ka1iIn7dAOABImRxDEktPn914hvMfbFrVhAJkgMo6iiv04E8Kq237Kdk_N2gunQiHtrUkIZ4n1M4DKybHjPBZAMDeXM3GqO0F5rbSP6Rg6BTQtFbD9unCpyFhcoU6ZhBMQ54dFZOVz1R6JWsrDTtU1ITHKMSi719OdbRU0l-LEDrHK10" />
                        <div class="absolute bottom-0 left-0 p-8 bg-gradient-to-t from-[#4d462e]/80 to-transparent w-full">
                            <p class="font-['Space_Grotesk'] text-white text-[10px] tracking-widest font-bold mb-2">UNIT_ID: X-PRO_01</p>
                            <h4 class="text-white text-2xl font-bold uppercase tracking-tight">Weather-Sealed Casings</h4>
                        </div>
                    </div>
                    <div class="bg-[#faf9f5] p-6 flex flex-col justify-between">
                        <div>
                            <span class="material-symbols-outlined text-[#4d462e] mb-4 block">ac_unit</span>
                            <h4 class="font-bold text-[#1b1c1a] uppercase tracking-tight mb-2">Arktik Ready</h4>
                            <p class="text-xs text-[#4a473d] leading-tight">Beroperasi penuh pada suhu hingga -30°C tanpa degradasi performa.</p>
                        </div>
                        <p class="font-['Space_Grotesk'] text-[10px] font-bold text-[#7b776c] uppercase mt-4">EXTREME_TEMP_SPEC</p>
                    </div>
                    <div class="bg-[#faf9f5] p-6 flex flex-col justify-between">
                        <div>
                            <span class="material-symbols-outlined text-[#4d462e] mb-4 block">water_drop</span>
                            <h4 class="font-bold text-[#1b1c1a] uppercase tracking-tight mb-2">Submersible</h4>
                            <p class="text-xs text-[#4a473d] leading-tight">Sertifikasi IP68 pada seluruh lini perangkat keras utama.</p>
                        </div>
                        <p class="font-['Space_Grotesk'] text-[10px] font-bold text-[#7b776c] uppercase mt-4">WATERPROOF_SPEC</p>
                    </div>
                    <div class="md:col-span-2 bg-[#4d462e] text-white p-12 flex items-center justify-between">
                        <div>
                            <h3 class="text-3xl font-black uppercase tracking-tighter mb-4">Eksplorasi Tanpa Batas</h3>
                            <p class="text-sm opacity-80 max-w-sm mb-6">Kami menyediakan toolkit lengkap untuk ekspedisi, mulai dari sistem energi modular hingga alat komunikasi satelit.</p>
                            <a href="{{ url('/katalog') }}"
                                class="inline-block bg-white text-[#4d462e] px-8 py-3 font-['Space_Grotesk'] font-bold text-xs tracking-widest uppercase hover:bg-[#ede2c1] transition-colors">
                                LIHAT KATALOG
                            </a>
                        </div>
                        <span class="material-symbols-outlined text-7xl opacity-20 hidden lg:block">vibration</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Basecamp Locations Section -->
        <section class="bg-[#faf9f5] py-24 px-8 md:px-24">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                    <div class="lg:col-span-4">
                        <p class="font-['Space_Grotesk'] text-[#4d462e] font-bold tracking-widest text-xs mb-4 uppercase">
                            NETWORK DISTRIBUTION
                        </p>
                        <h2 class="text-5xl font-black text-[#1b1c1a] tracking-tighter uppercase mb-8 leading-none">
                            LOKASI<br />BASECAMP
                        </h2>
                        <div class="space-y-8">
                            <div class="group cursor-pointer">
                                <div class="flex justify-between items-center border-b-2 border-[#ccc6b9] pb-4 group-hover:border-[#4d462e] transition-colors">
                                    <div>
                                        <h4 class="font-black text-xl uppercase tracking-tight">BASECAMP ALPHA</h4>
                                        <p class="font-['Space_Grotesk'] text-[10px] text-[#4a473d] tracking-widest font-bold">JAKARTA SELATAN - HUB PUSAT</p>
                                    </div>
                                    <span class="material-symbols-outlined text-[#4d462e]">location_on</span>
                                </div>
                            </div>
                            <div class="group cursor-pointer">
                                <div class="flex justify-between items-center border-b-2 border-[#ccc6b9] pb-4 group-hover:border-[#4d462e] transition-colors">
                                    <div>
                                        <h4 class="font-black text-xl uppercase tracking-tight">BASECAMP BRAVO</h4>
                                        <p class="font-['Space_Grotesk'] text-[10px] text-[#4a473d] tracking-widest font-bold">BANDUNG - DIVISI TEKNIS</p>
                                    </div>
                                    <span class="material-symbols-outlined text-[#4d462e]">location_on</span>
                                </div>
                            </div>
                            <div class="group cursor-pointer opacity-50">
                                <div class="flex justify-between items-center border-b-2 border-[#ccc6b9] pb-4">
                                    <div>
                                        <h4 class="font-black text-xl uppercase tracking-tight">BASECAMP CHARLIE</h4>
                                        <p class="font-['Space_Grotesk'] text-[10px] text-[#4a473d] tracking-widest font-bold">MALANG - OUTPOST TIMUR (SOON)</p>
                                    </div>
                                    <span class="material-symbols-outlined">construction</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-8 h-[500px] bg-[#dbdad6] relative overflow-hidden flex items-center justify-center">
                        <div class="absolute inset-0 grayscale contrast-125 opacity-40">
                            <img alt="Topographic Map"
                                class="w-full h-full object-cover"
                                src="https://lh3.googleusercontent.com/aida-public/AB6AXuC1yXnk2JOzmz62aQtGV-ut-pwgY_SCSdSA0KwwtDUrLJPonFZ9tmGkWHdbdmLS60RO5B9vQ-GGNF23IDkr2IkIQvVNP20TmDWyXpLyOrer55FKDYbrhE6YGN0W9WTxuj3vjN9164XDR5eiiVpuE96XXID9fI2mhGJhNfADqy3HT-o67x61Z2s8IxwuuGqYt-WKvIO80vY8loP15z_ASpuw_Cbb8W2dwGSHlLOgmhj5RxI9H2mjAbmJqHUpvMaoWhPzOezigJd1XVk" />
                        </div>
                        <div class="relative z-10 p-12 bg-[#faf9f5]/90 backdrop-blur-md shadow-2xl max-w-sm">
                            <p class="font-['Space_Grotesk'] text-[10px] font-bold text-[#4d462e] tracking-[0.4em] mb-4 uppercase">
                                COORDINATE SYSTEM
                            </p>
                            <div class="space-y-2 mb-6">
                                <p class="text-2xl font-black font-['Space_Grotesk'] tracking-tighter">6.1751° S</p>
                                <p class="text-2xl font-black font-['Space_Grotesk'] tracking-tighter">106.8272° E</p>
                            </div>
                            <p class="text-xs text-[#4a473d] mb-6 uppercase tracking-wider font-bold">
                                Operasional harian 08:00 - 21:00. Pengerahan darurat tersedia melalui otorisasi khusus.
                            </p>
                            <button class="w-full bg-[#4d462e] text-white py-4 font-['Space_Grotesk'] font-bold text-xs tracking-[0.2em] uppercase hover:bg-[#655e44] transition-colors">
                                BUKA NAVIGASI
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-[#1b1c1a] text-white py-16 px-8 md:px-24">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-start md:items-center gap-12">
            <div>
                <div class="text-3xl font-black tracking-tighter uppercase mb-4">MAJELIS RENTAL</div>
                <p class="font-['Space_Grotesk'] text-xs tracking-widest text-[#d0c6a6] uppercase">
                    OPERATING OUT OF BASE CAMP ALPHA
                </p>
            </div>
            <div class="flex flex-wrap gap-12">
                <div>
                    <h5 class="font-['Space_Grotesk'] text-[10px] font-bold text-[#7b776c] tracking-widest uppercase mb-4">RESOURCES</h5>
                    <ul class="space-y-2 text-sm font-bold uppercase tracking-tight">
                        <li><a class="hover:text-[#d0c6a6] transition-colors" href="#">Panduan</a></li>
                        <li><a class="hover:text-[#d0c6a6] transition-colors" href="{{ url('/katalog') }}">Inventaris</a></li>
                        <li><a class="hover:text-[#d0c6a6] transition-colors" href="#">Kontak</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-['Space_Grotesk'] text-[10px] font-bold text-[#7b776c] tracking-widest uppercase mb-4">LEGAL</h5>
                    <ul class="space-y-2 text-sm font-bold uppercase tracking-tight">
                        <li><a class="hover:text-[#d0c6a6] transition-colors" href="#">Ketentuan Layanan</a></li>
                        <li><a class="hover:text-[#d0c6a6] transition-colors" href="#">Kebijakan Privasi</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto mt-16 pt-8 border-t border-white/10 flex justify-between items-center">
            <p class="font-['Space_Grotesk'] text-[8px] tracking-[0.3em] text-white/40 uppercase">
                © 2025 MAJELIS RENTAL LOGISTICS. ALL RIGHTS RESERVED.
            </p>
            <div class="flex gap-4">
                <span class="material-symbols-outlined text-white/40 text-sm">shield</span>
                <span class="material-symbols-outlined text-white/40 text-sm">security</span>
            </div>
        </div>
    </footer>

</body>
</html>
