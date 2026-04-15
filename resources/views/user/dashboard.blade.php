@extends('user.layouts.user')

@section('title', 'Dashboard')

@section('content')

{{-- Page Header --}}
<div class="mb-7">
    <p class="text-[10px] uppercase tracking-[0.25em] font-bold mb-1" style="color: #a09880;">SELAMAT DATANG</p>
    <h1 class="text-2xl font-black tracking-tight" style="color: #2f342e;">
        Halo, {{ explode(' ', Auth::user()->name)[0] }} 👋
    </h1>
    <p class="text-xs mt-1" style="color: #7b776c;">{{ now()->translatedFormat('l, d F Y') }}</p>
</div>

{{-- Stats Grid --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-7">
    @php
        $stats = [
            [
                'label' => 'Total Pesanan',
                'value' => $totalPesanan,
                'icon'  => 'receipt_long',
                'icon_color' => '#655e44',
                'icon_bg'    => '#F2E8C6',
                'link'  => route('user.pesanan.index'),
            ],
            [
                'label' => 'Sedang Berjalan',
                'value' => $pesananBerjalan,
                'icon'  => 'moving',
                'icon_color' => '#166534',
                'icon_bg'    => '#dcfce7',
                'link'  => route('user.pesanan.index', ['status' => 'berjalan']),
            ],
            [
                'label' => 'Menunggu Bayar',
                'value' => $menungguBayar,
                'icon'  => 'schedule',
                'icon_color' => '#92400e',
                'icon_bg'    => '#fef3c7',
                'link'  => route('user.pesanan.index', ['status' => 'menunggu_pembayaran']),
            ],
            [
                'label' => 'Tagihan Denda',
                'value' => $tagihanDenda,
                'icon'  => 'gavel',
                'icon_color' => '#991b1b',
                'icon_bg'    => '#fee2e2',
                'link'  => route('user.pesanan.index', ['status' => 'terlambat']),
            ],
        ];
    @endphp

    @foreach($stats as $stat)
        <a href="{{ $stat['link'] }}"
           class="block rounded-xl p-4 transition-all hover:-translate-y-0.5 group"
           style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-3 transition-transform group-hover:scale-110"
                 style="background: {{ $stat['icon_bg'] }};">
                <span class="material-symbols-outlined text-xl" style="color: {{ $stat['icon_color'] }};">{{ $stat['icon'] }}</span>
            </div>
            <p class="text-2xl font-black mb-0.5" style="color: #2f342e;">{{ $stat['value'] }}</p>
            <p class="text-[10px] uppercase tracking-wider font-semibold leading-tight" style="color: #7b776c;">{{ $stat['label'] }}</p>
        </a>
    @endforeach
</div>

{{-- Denda Alert --}}
@if($tagihanDenda > 0)
    <div class="rounded-xl px-4 py-3.5 mb-6 flex items-center gap-3"
         style="background: #fef2f2; border: 1px solid #fecaca;">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background: #fee2e2;">
            <span class="material-symbols-outlined text-lg" style="color: #dc2626;">warning</span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-black uppercase tracking-wider" style="color: #991b1b;">
                {{ $tagihanDenda }} Tagihan Denda Belum Dibayar
            </p>
            <p class="text-[10px] mt-0.5" style="color: #b91c1c;">
                Segera selesaikan untuk menghindari pemblokiran akun.
            </p>
        </div>
        <a href="{{ route('user.pesanan.index', ['status' => 'terlambat']) }}"
           class="flex-shrink-0 flex items-center gap-1.5 text-white text-[10px] font-black uppercase tracking-wider px-3 py-2 rounded-lg transition-all hover:opacity-90"
           style="background: #dc2626;">
            <span class="material-symbols-outlined text-sm">open_in_new</span>
            Lihat
        </a>
    </div>
@endif

{{-- Active + History Grid --}}
<div class="grid lg:grid-cols-2 gap-5">

    {{-- Pesanan Aktif --}}
    <div class="rounded-xl overflow-hidden" style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
        <div class="flex items-center justify-between px-4 py-3" style="background: #f4f2ec;">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-base" style="color: #655e44;">bolt</span>
                <p class="text-xs font-black uppercase tracking-widest" style="color: #2f342e;">Pesanan Aktif</p>
            </div>
            <a href="{{ route('user.pesanan.index') }}"
               class="text-[10px] font-bold uppercase tracking-wider flex items-center gap-0.5 transition-colors hover:opacity-70"
               style="color: #655e44;">
                Semua
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </a>
        </div>

        <div>
            @forelse($pesananAktif as $item)
                <a href="{{ route('user.pesanan.show', $item->id) }}"
                   class="flex items-center justify-between px-4 py-3.5 transition-colors hover:bg-[#faf9f5] group"
                   style="border-bottom: 1px solid #f4f2ec;">
                    <div class="min-w-0 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                             style="background: #F2E8C6;">
                            <span class="material-symbols-outlined text-sm" style="color: #655e44;">receipt</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold truncate transition-colors group-hover:text-[#655e44]"
                               style="color: #2f342e;">
                                {{ $item->nomor_transaksi }}
                            </p>
                            <p class="text-[10px] uppercase tracking-wider mt-0.5" style="color: #7b776c;">
                                {{ $item->created_at->format('d M Y') }}
                            </p>
                        </div>
                    </div>
                    @include('user.components.status-badge', ['status' => $item->status])
                </a>
            @empty
                <div class="px-4 py-10 text-center">
                    <span class="material-symbols-outlined text-4xl block mb-2" style="color: #ccc6b9;">inbox</span>
                    <p class="text-xs uppercase tracking-wider" style="color: #7b776c;">Tidak ada pesanan aktif</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Riwayat --}}
    <div class="rounded-xl overflow-hidden" style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
        <div class="flex items-center justify-between px-4 py-3" style="background: #f4f2ec;">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-base" style="color: #7b776c;">history</span>
                <p class="text-xs font-black uppercase tracking-widest" style="color: #2f342e;">Riwayat Terakhir</p>
            </div>
            <a href="{{ route('user.pesanan.index', ['status' => 'selesai']) }}"
               class="text-[10px] font-bold uppercase tracking-wider flex items-center gap-0.5 transition-colors hover:opacity-70"
               style="color: #655e44;">
                Semua
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </a>
        </div>

        <div>
            @forelse($riwayatSingkat as $item)
                <a href="{{ route('user.pesanan.show', $item->id) }}"
                   class="flex items-center justify-between px-4 py-3.5 transition-colors hover:bg-[#faf9f5] group"
                   style="border-bottom: 1px solid #f4f2ec;">
                    <div class="min-w-0 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                             style="background: #e7e4dc;">
                            <span class="material-symbols-outlined text-sm" style="color: #7b776c;">receipt</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold truncate transition-colors group-hover:text-[#2f342e]"
                               style="color: #7b776c;">
                                {{ $item->nomor_transaksi }}
                            </p>
                            <p class="text-[10px] uppercase tracking-wider mt-0.5" style="color: #a09880;">
                                {{ $item->created_at->format('d M Y') }}
                            </p>
                        </div>
                    </div>
                    @include('user.components.status-badge', ['status' => $item->status])
                </a>
            @empty
                <div class="px-4 py-10 text-center">
                    <span class="material-symbols-outlined text-4xl block mb-2" style="color: #ccc6b9;">history</span>
                    <p class="text-xs uppercase tracking-wider" style="color: #7b776c;">Belum ada riwayat</p>
                </div>
            @endforelse
        </div>
    </div>

</div>

{{-- Quick Actions --}}
<div class="mt-5">
    <p class="text-[10px] font-black uppercase tracking-[0.2em] mb-3" style="color: #a09880;">Aksi Cepat</p>
    <div class="grid grid-cols-3 gap-3">
        @php
            $quickActions = [
                ['href' => url('/katalog'),                    'icon' => 'explore',         'label' => 'Katalog'],
                ['href' => route('user.pesanan.index'),        'icon' => 'receipt_long',    'label' => 'Pesanan'],
                ['href' => route('user.profil.edit'),          'icon' => 'manage_accounts', 'label' => 'Profil'],
            ];
        @endphp
        @foreach($quickActions as $action)
            <a href="{{ $action['href'] }}"
               class="flex flex-col items-center gap-2.5 rounded-xl p-4 text-center transition-all hover:-translate-y-0.5 group"
               style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors group-hover:bg-[#F2E8C6]"
                     style="background: #f4f2ec;">
                    <span class="material-symbols-outlined text-xl transition-colors group-hover:text-[#655e44]"
                          style="color: #7b776c;">{{ $action['icon'] }}</span>
                </div>
                <p class="text-[10px] font-black uppercase tracking-wider" style="color: #4d4a3e;">{{ $action['label'] }}</p>
            </a>
        @endforeach
    </div>
</div>

@endsection
