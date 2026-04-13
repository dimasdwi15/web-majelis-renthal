<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Majelis Renthal</title>

    {{-- FIX: Hapus CDN Tailwind — bentrok dengan Vite build --}}
    {{-- Gunakan Vite saja, sama seperti katalog.blade.php --}}
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')

    {{-- FIX: Tambah theme.css agar custom color classes (bg-surface-container, dll) bekerja --}}
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">

    {{-- FIX: Tambah Material Symbols — dibutuhkan navbar & ikon halaman --}}
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">
</head>

<body class="bg-[#faf9f5] font-inter">

    @include('user.components.navbar')

    <!-- Hero Section -->
    <section class="relative h-screen flex items-center justify-center overflow-hidden">

        <!-- Background Image -->
        <div class="absolute inset-0 z-0 overflow-hidden">
            <img alt="Rugged Mountains"
                class="w-full h-full object-cover contrast-[1.05] grayscale-[0.15] brightness-90 transition-transform duration-700 ease-in-out transform hover:scale-110 origin-center"
                src="{{ asset('images/hero.jpg') }}">
            <div class="absolute inset-0 bg-gradient-to-t from-[#faf9f5]/40 via-[#f4f4ef]/20 to-transparent"></div>
        </div>

        <!-- Hero Content -->
        <div class="relative z-10 text-center px-6 max-w-5xl">

            <!-- Badge -->
            <div class="inline-block bg-[#655e44]/90 backdrop-blur-md px-6 py-2 mb-6 rounded-lg shadow-md">
                <p class="text-[#F2E8C6] text-xs font-bold uppercase tracking-widest">Deploying Excellence Since 2025</p>
            </div>

            <!-- Hero Title -->
            <h1 class="text-5xl sm:text-6xl md:text-7xl lg:text-8xl font-extrabold uppercase tracking-tight leading-tight mb-8 text-[#251D1D]">
                Gear Tangguh<br>Tanpa Kompromi
            </h1>

            <!-- Buttons -->
            <div class="flex flex-col md:flex-row gap-6 justify-center items-center">
                <button
                    class="bg-[#655e44] text-[#F2E8C6] px-12 py-5 rounded-lg font-bold uppercase tracking-wide flex items-center gap-3 shadow-lg hover:ring-2 hover:ring-[#655e44]/50 transition-all transform hover:scale-105 duration-300"
                    onclick="window.location.href='/katalog'">
                    EXPLORE REPOSITORY
                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                </button>
            </div>

        </div>
    </section>

    <!-- Categories Grid -->
    <section class="bg-[#efeeea] py-24 px-6">
        <div class="max-w-screen-2xl mx-auto">
            <div class="mb-12">
                <p class="text-xs font-bold text-[#4d462e] uppercase tracking-widest mb-2">Technical Categories</p>
                <h2 class="text-4xl font-black text-[#4d462e] uppercase tracking-tighter">Gear Inventory</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                <!-- Tenda -->
                <a href="{{ url('/katalog?kategori=tenda') }}"
                    class="group bg-white p-1 rounded-xl transition-all hover:bg-[#4d462e] duration-300 block">
                    <div class="bg-[#f4f4f0] p-6 rounded-lg h-full flex flex-col group-hover:bg-[#655e44] transition-all">
                        <div class="mb-8 overflow-hidden rounded-lg">
                            <img class="w-full h-48 object-cover grayscale hover:grayscale-0 transition-all duration-500"
                                alt="Tenda Gunung"
                                src="https://lh3.googleusercontent.com/aida-public/AB6AXuAOWjRYEbRkozqd1UUBTGciCju1JezTBsfBBrFSdk5hcGAbVr_qxssr9kKvJr9dwxlwrIbiPusfwuZF2FhiJmZttAFzUGafedYkeqKaPwIYEEt_32M6O8Xb0JZ4O7cpk-WRMARPj7afkaSTO_SR7QqzObmmzS8XD8L77v6hog5FmMSYUJXheZS9M5AI4VdlXUbQG2-kBAlmNdkuT5p7qwwshovv-0ZrbJHRxhq5u-YePe7YVvFkavqatosl5YKY80D4s0O6WWw1Jek" />
                        </div>
                        <p class="text-[10px] font-bold text-[#4d462e] group-hover:text-[#F2E8C6]/70 uppercase tracking-widest">Type: Shelter</p>
                        <h3 class="text-2xl font-black text-[#4d462e] group-hover:text-[#F2E8C6] uppercase mt-1">Tenda</h3>
                        <p class="text-[#4a473d] group-hover:text-[#F2E8C6]/80 text-sm mt-3 leading-relaxed">
                            Shelter teknis untuk kondisi ekstrem. Tahan badai, ringan, dan presisi.
                        </p>
                        <div class="mt-auto pt-6 flex justify-between items-center">
                            <span class="text-xs font-bold text-[#4d462e] group-hover:text-[#F2E8C6] uppercase tracking-widest">14 Units Available</span>
                            <span class="material-symbols-outlined text-[#4d462e] group-hover:text-[#F2E8C6]">north_east</span>
                        </div>
                    </div>
                </a>

                <!-- Tas/Carrier -->
                <a href="{{ url('/katalog?kategori=tas') }}"
                    class="group bg-white p-1 rounded-xl transition-all hover:bg-[#4d462e] duration-300 block">
                    <div class="bg-[#f4f4f0] p-6 rounded-lg h-full flex flex-col group-hover:bg-[#655e44] transition-all">
                        <div class="mb-8 overflow-hidden rounded-lg">
                            <img class="w-full h-48 object-cover grayscale hover:grayscale-0 transition-all duration-500"
                                alt="Tas Carrier"
                                src="https://lh3.googleusercontent.com/aida-public/AB6AXuBTl5M8V1I54ShJS4iEKq6rV6NMStqKqauCFicYWnMIPRuPK11aWTpMuVmoAZOvOBZ0aLKpCUR2b1Wl7tStLJeIPmTQW6BnLhGf1tehZXqmzVU22jATrWUKZb7W-Yza34ooE3vtWW_TEdcR5RlTRn_L9pGOkvdxeQqRURWsMMIDTyKHjnLLrqodlICqugnjdDuz7sknn9nh9UnhEhvdnqr_9ngre1mnSJ93RoCOQd6tvXO59NdK3ckaiFUDFN-yPXA2I2fOs_dqpDc" />
                        </div>
                        <p class="text-[10px] font-bold text-[#4d462e] group-hover:text-[#F2E8C6]/70 uppercase tracking-widest">Type: Load Carriage</p>
                        <h3 class="text-2xl font-black text-[#4d462e] group-hover:text-[#F2E8C6] uppercase mt-1">Tas/Carrier</h3>
                        <p class="text-[#4a473d] group-hover:text-[#F2E8C6]/80 text-sm mt-3 leading-relaxed">
                            Ergonomi maksimal untuk beban berat. Material cordura mil-spec.
                        </p>
                        <div class="mt-auto pt-6 flex justify-between items-center">
                            <span class="text-xs font-bold text-[#4d462e] group-hover:text-[#F2E8C6] uppercase tracking-widest">28 Units Available</span>
                            <span class="material-symbols-outlined text-[#4d462e] group-hover:text-[#F2E8C6]">north_east</span>
                        </div>
                    </div>
                </a>

                <!-- Alat Masak -->
                <a href="{{ url('/katalog?kategori=masak') }}"
                    class="group bg-white p-1 rounded-xl transition-all hover:bg-[#4d462e] duration-300 block">
                    <div class="bg-[#f4f4f0] p-6 rounded-lg h-full flex flex-col group-hover:bg-[#655e44] transition-all">
                        <div class="mb-8 overflow-hidden rounded-lg">
                            <img class="w-full h-48 object-cover grayscale hover:grayscale-0 transition-all duration-500"
                                alt="Alat Masak"
                                src="https://lh3.googleusercontent.com/aida-public/AB6AXuCVOtftWVts0q5n6HQ7LGvfyUCL4XY1XD584pahguQsYJanQ-KveUJ-gEpo0CmYXVu0r45Y07RVCA13mq_XJ_NnaK--JihkW-CPDu9fKjALjzt8UtYm6OtZUcO5epf0neO1QxVHKpjG3JGjvZqFooXUMKTteeDOqM_eauPaBB1Fit--H4hZ_08qoJ9QMB8AXMPIk_ozLDPJeyAs83YS4Hwh8umfzZstb0xYtO6ohFAaRC11UKKxYSZjENz5VQC2kR7z_cPboHJ3HcQ" />
                        </div>
                        <p class="text-[10px] font-bold text-[#4d462e] group-hover:text-[#F2E8C6]/70 uppercase tracking-widest">Type: Sustainment</p>
                        <h3 class="text-2xl font-black text-[#4d462e] group-hover:text-[#F2E8C6] uppercase mt-1">Alat Masak</h3>
                        <p class="text-[#4a473d] group-hover:text-[#F2E8C6]/80 text-sm mt-3 leading-relaxed">
                            Sistem pembakaran efisien tinggi untuk logistik di lapangan.
                        </p>
                        <div class="mt-auto pt-6 flex justify-between items-center">
                            <span class="text-xs font-bold text-[#4d462e] group-hover:text-[#F2E8C6] uppercase tracking-widest">42 Units Available</span>
                            <span class="material-symbols-outlined text-[#4d462e] group-hover:text-[#F2E8C6]">north_east</span>
                        </div>
                    </div>
                </a>

                <!-- Penerangan -->
                <a href="{{ url('/katalog?kategori=penerangan') }}"
                    class="group bg-white p-1 rounded-xl transition-all hover:bg-[#4d462e] duration-300 block">
                    <div class="bg-[#f4f4f0] p-6 rounded-lg h-full flex flex-col group-hover:bg-[#655e44] transition-all">
                        <div class="mb-8 overflow-hidden rounded-lg">
                            <img class="w-full h-48 object-cover grayscale hover:grayscale-0 transition-all duration-500"
                                alt="Penerangan"
                                src="https://lh3.googleusercontent.com/aida-public/AB6AXuCHnRDKEuplSYeYpe8nLVEe-a-uMnwEfYAk5W71Q2odgWgxYDQK7HySGWp6jq-MaBz_sW33b9aIo5AEnqy6oAP8llcf7RFdz2D2XvZl87m1_3OXZra_ZFIqffOCnrhkWrfYOBLH5pZw5ekAVnd3F6FPQ2pEdQBP60OYr1-GwcpGKAvj68hApoF5HoXw_kt6W_fTNsPNLle47FZZhRHA9tKm1Mf_Fjgmmfa1vZo2-RuDtbYWVZ5NwAP_2KvsO9jmNTXUuG07BllcbUA" />
                        </div>
                        <p class="text-[10px] font-bold text-[#4d462e] group-hover:text-[#F2E8C6]/70 uppercase tracking-widest">Type: Illumination</p>
                        <h3 class="text-2xl font-black text-[#4d462e] group-hover:text-[#F2E8C6] uppercase mt-1">Penerangan</h3>
                        <p class="text-[#4a473d] group-hover:text-[#F2E8C6]/80 text-sm mt-3 leading-relaxed">
                            Lumen tinggi, daya tahan baterai ekstrem. Tactical illumination.
                        </p>
                        <div class="mt-auto pt-6 flex justify-between items-center">
                            <span class="text-xs font-bold text-[#4d462e] group-hover:text-[#F2E8C6] uppercase tracking-widest">19 Units Available</span>
                            <span class="material-symbols-outlined text-[#4d462e] group-hover:text-[#F2E8C6]">north_east</span>
                        </div>
                    </div>
                </a>

            </div>
        </div>
    </section>

    <!-- Featured Gear Selection -->
    <section class="bg-[#faf9f5] py-24 px-6 overflow-hidden">
        <div class="max-w-screen-2xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-6">
                <div>
                    <p class="text-xs font-bold text-[#4d462e] uppercase tracking-[0.3em] mb-4">Curated Selection</p>
                    <h2 class="text-6xl font-black text-[#4d462e] uppercase tracking-tighter leading-none">
                        Ready for<br />Deployment
                    </h2>
                </div>
                <div class="bg-[#efeeea] p-6 rounded-lg max-w-md">
                    <p class="text-xs font-bold text-[#4a473d] uppercase mb-2">Note for Operators</p>
                    <p class="text-sm text-[#1b1c1a] italic">"Semua gear telah melewati inspeksi teknis 24-titik sebelum tersedia untuk penyewaan."</p>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

                <!-- Gear Item 1 -->
                <div class="flex flex-col gap-6">
                    <div class="relative bg-[#f4f4f0] p-4 rounded-lg">
                        <img class="w-full aspect-[4/5] object-cover grayscale contrast-125"
                            alt="Apex-40 Stormbreaker"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuByynQ6ThI2NK811SGz9oFUMFbmZJqcP445VGNQH-juG9vp7NTRCy0HYeiap8twfnYI7VPf7t-cihBg3COBU0wuYziM4UtrWy4fgAxGJ4ZWcAhkIxuGpYmf_ZB-ilKfIwkb8o08UbSQwDkC86hI0QSbUdr7N2EPcamHqE38cBg8GQFogXEJdZWvN7Ut3QKL4pZl_t64AjQmrGFJpBGs4l5b11AO5FFua4RCqIFCNIeUDoFrdy_31kB30_Fu-EAv8d4mnQYpzFH427o" />
                        <div class="absolute top-8 left-8 bg-emerald-600 text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest rounded-sm">
                            Tersedia
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-start">
                            <h3 class="text-3xl font-black text-[#4d462e] uppercase leading-tight">Apex-40 Stormbreaker</h3>
                            <p class="text-xl font-bold text-[#4d462e]">IDR 85K/d</p>
                        </div>
                        <div class="flex gap-2 mb-4 flex-wrap">
                            <span class="bg-[#efeeea] px-2 py-1 text-[10px] font-bold text-[#4a473d] uppercase">40 Liters</span>
                            <span class="bg-[#efeeea] px-2 py-1 text-[10px] font-bold text-[#4a473d] uppercase">Weatherproof</span>
                            <span class="bg-[#efeeea] px-2 py-1 text-[10px] font-bold text-[#4a473d] uppercase">1.2KG</span>
                        </div>
                        <a href="{{ url('/katalog') }}"
                            class="w-full block text-center bg-[#655e44] text-[#F2E8C6] py-4 rounded-lg font-bold uppercase tracking-tight hover:bg-[#4d462e] transition-all">
                            RENT NOW
                        </a>
                    </div>
                </div>

                <!-- Gear Item 2 -->
                <div class="flex flex-col gap-6">
                    <div class="relative bg-[#f4f4f0] p-4 rounded-lg">
                        <img class="w-full aspect-[4/5] object-cover grayscale contrast-125"
                            alt="Titan-Lume 3000"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuBr73ABUJvneXE6c3an66KB5m_aZXd9jrlRjETYnWmUtt7w5R0_3FlKtledAvy36oSVP_0oULKc1J2FghNfWzfWWeN0PI3EZwgujFe8eDl-OxahUhUlD0NKXYx_Ry8y9WGa0KYi7i6svhoNMsFj3VmRKDKfcuzJRC5D-czd7bLkovH6bGTnIYojZFNbXAZu6kz_5yWsroQjUvJESJoaTJEsTjKuK8TNX9gVeyZGdnkIC0d88qjc_zXj8x3aZZzkjOwiXDJxFetxRYY" />
                        <div class="absolute top-8 left-8 bg-emerald-600 text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest rounded-sm">
                            Tersedia
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-start">
                            <h3 class="text-3xl font-black text-[#4d462e] uppercase leading-tight">Titan-Lume 3000</h3>
                            <p class="text-xl font-bold text-[#4d462e]">IDR 45K/d</p>
                        </div>
                        <div class="flex gap-2 mb-4 flex-wrap">
                            <span class="bg-[#efeeea] px-2 py-1 text-[10px] font-bold text-[#4a473d] uppercase">3000 Lumens</span>
                            <span class="bg-[#efeeea] px-2 py-1 text-[10px] font-bold text-[#4a473d] uppercase">24H Runtime</span>
                            <span class="bg-[#efeeea] px-2 py-1 text-[10px] font-bold text-[#4a473d] uppercase">Impact-Res</span>
                        </div>
                        <a href="{{ url('/katalog') }}"
                            class="w-full block text-center bg-[#655e44] text-[#F2E8C6] py-4 rounded-lg font-bold uppercase tracking-tight hover:bg-[#4d462e] transition-all">
                            RENT NOW
                        </a>
                    </div>
                </div>

                <!-- Gear Item 3 -->
                <div class="flex flex-col gap-6">
                    <div class="relative bg-[#f4f4f0] p-4 rounded-lg">
                        <img class="w-full aspect-[4/5] object-cover grayscale contrast-125"
                            alt="MSR Core-Cook Kit"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuC4SaxYehv6twnTLGPjMrTnhyP_wQsPQoVfDuX_GbFiDrGWRNkH5KdAdtoZKbUBcVbg93hmoSuCT5iA5MHAyU3ZXaIRVVtRtrOczTa-CkZXjlQO85yDqSKIaTvJQ-dkSvUIfICGWFvKjiyw9Ic688kF0f7QVukHTp2yR3974szsaTpjwOljqpGjatUBoSEbxlC1xXbnD81dWOqRqFUZcTl3-m-LS2koQUFOm6LAS-DHGJTSFKDgHVtxq1_BF9Iro0KTBhitSUg0Dnk" />
                        <div class="absolute top-8 left-8 bg-emerald-600 text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest rounded-sm">
                            Tersedia
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-start">
                            <h3 class="text-3xl font-black text-[#4d462e] uppercase leading-tight">MSR Core-Cook Kit</h3>
                            <p class="text-xl font-bold text-[#4d462e]">IDR 60K/d</p>
                        </div>
                        <div class="flex gap-2 mb-4 flex-wrap">
                            <span class="bg-[#efeeea] px-2 py-1 text-[10px] font-bold text-[#4a473d] uppercase">Titanium</span>
                            <span class="bg-[#efeeea] px-2 py-1 text-[10px] font-bold text-[#4a473d] uppercase">Ultralight</span>
                            <span class="bg-[#efeeea] px-2 py-1 text-[10px] font-bold text-[#4a473d] uppercase">Stackable</span>
                        </div>
                        <a href="{{ url('/katalog') }}"
                            class="w-full block text-center bg-[#655e44] text-[#F2E8C6] py-4 rounded-lg font-bold uppercase tracking-tight hover:bg-[#4d462e] transition-all">
                            RENT NOW
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    @include('user.components.footer')

</body>

</html>
