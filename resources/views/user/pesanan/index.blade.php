@extends('user.layouts.user')

@section('title', 'Pesanan Saya')

@section('content')

{{-- Header --}}
<div class="mb-6">
    <p class="text-[10px] uppercase tracking-[0.2em] font-bold mb-1" style="color: #a09880;">RIWAYAT & STATUS</p>
    <h1 class="text-2xl font-black tracking-tight" style="color: #2f342e;">Pesanan Saya</h1>
</div>

{{-- Filter Tabs --}}
<div class="mb-4 overflow-x-auto scrollbar-hide">
    <div class="flex gap-1.5 pb-1 w-max min-w-full">
        @php
            $tabs = [
                'semua'               => ['label' => 'Semua',         'icon' => 'format_list_bulleted'],
                'menunggu_pembayaran' => ['label' => 'Menunggu Bayar','icon' => 'schedule'],
                'berjalan'            => ['label' => 'Berjalan',      'icon' => 'moving'],
                'terlambat'           => ['label' => 'Terlambat',     'icon' => 'timer_off'],
                'selesai'             => ['label' => 'Selesai',       'icon' => 'check_circle'],
                'dibatalkan'          => ['label' => 'Dibatalkan',    'icon' => 'cancel'],
            ];
            $activeTab = request('status', 'semua');
        @endphp

        @foreach($tabs as $key => $tab)
            <a href="{{ request()->fullUrlWithQuery(['status' => $key === 'semua' ? null : $key, 'page' => null]) }}"
               class="flex-shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all whitespace-nowrap"
               style="{{ $activeTab === $key
                   ? 'background: #655e44; color: #F2E8C6;'
                   : 'background: #fff; border: 1px solid rgba(101,94,68,0.18); color: #7b776c;' }}">
                <span class="material-symbols-outlined text-sm">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</div>

{{-- Search & Sort --}}
<form method="GET" action="{{ route('user.pesanan.index') }}" class="flex gap-2 mb-5">
    @if(request('status'))
        <input type="hidden" name="status" value="{{ request('status') }}">
    @endif
    <div class="relative flex-1">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-base pointer-events-none"
              style="color: #a09880;">search</span>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Cari nomor transaksi..."
               class="w-full pl-9 pr-4 py-2.5 rounded-lg text-xs focus:outline-none focus:ring-2 transition-all"
               style="background: #fff; border: 1px solid rgba(101,94,68,0.18); color: #2f342e;
                      box-shadow: 0 1px 4px rgba(37,29,29,0.05);"
               onfocus="this.style.borderColor='#655e44'; this.style.boxShadow='0 0 0 2px rgba(101,94,68,0.18)';"
               onblur="this.style.borderColor='rgba(101,94,68,0.18)'; this.style.boxShadow='0 1px 4px rgba(37,29,29,0.05)';">
    </div>
    <select name="sort" onchange="this.form.submit()"
            class="text-[10px] font-semibold uppercase tracking-wider px-3 py-2.5 rounded-lg focus:outline-none cursor-pointer transition-all"
            style="background: #fff; border: 1px solid rgba(101,94,68,0.18); color: #4d4a3e;
                   box-shadow: 0 1px 4px rgba(37,29,29,0.05);">
        <option value="terbaru" {{ request('sort', 'terbaru') === 'terbaru' ? 'selected' : '' }}>Terbaru</option>
        <option value="terlama" {{ request('sort') === 'terlama' ? 'selected' : '' }}>Terlama</option>
    </select>
    <button type="submit"
            class="flex items-center justify-center w-10 h-10 rounded-lg transition-all hover:opacity-80"
            style="background: #F2E8C6; border: 1px solid rgba(101,94,68,0.3); color: #655e44;">
        <span class="material-symbols-outlined text-base">search</span>
    </button>
</form>

{{-- Pesanan List --}}
@forelse($pesanan as $trx)
    <div class="mb-4 rounded-xl overflow-hidden transition-all hover:-translate-y-0.5"
         style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">

        {{-- Transaction Header --}}
        <div class="flex items-center justify-between px-4 py-3" style="background: #f4f2ec; border-bottom: 1px solid rgba(101,94,68,0.08);">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                     style="background: #F2E8C6;">
                    <span class="material-symbols-outlined text-sm" style="color: #655e44;">receipt</span>
                </div>
                <div>
                    <p class="text-sm font-black tracking-tight" style="color: #2f342e;">{{ $trx->nomor_transaksi }}</p>
                    <p class="text-[10px] uppercase tracking-wider font-semibold" style="color: #7b776c;">
                        {{ $trx->created_at->format('d M Y, H:i') }}
                    </p>
                </div>
            </div>
            @include('user.components.status-badge', ['status' => $trx->status])
        </div>

        {{-- Items — show ALL items in this transaction grouped together --}}
        <div class="px-4 pt-3 pb-0">
            <p class="text-[9px] font-black uppercase tracking-widest mb-2.5" style="color: #a09880;">
                Barang Disewa ({{ $trx->details->count() }} item)
            </p>
            <div class="space-y-2 pb-3" style="border-bottom: 1px solid #f4f2ec;">
                @foreach($trx->details as $detail)
                    <div class="flex items-center gap-3">
                        {{-- Item Image --}}
                        <div class="w-12 h-12 rounded-lg overflow-hidden flex-shrink-0"
                             style="background: #f4f2ec;">
                            @if($detail->barang->fotoUtama)
                                <img src="{{ Storage::url($detail->barang->fotoUtama->path_foto) }}"
                                     alt="{{ $detail->barang->nama }}"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <span class="material-symbols-outlined text-lg" style="color: #ccc6b9;">image_not_supported</span>
                                </div>
                            @endif
                        </div>

                        {{-- Item Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold truncate" style="color: #2f342e;">{{ $detail->barang->nama }}</p>
                            <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md"
                                      style="background: #F2E8C6; color: #655e44;">
                                    ×{{ $detail->jumlah }}
                                </span>
                                <span class="text-[10px]" style="color: #7b776c;">
                                    Rp {{ number_format($detail->harga_per_hari, 0, ',', '.') }}/hari
                                    × {{ $detail->durasi_hari }} hari
                                </span>
                            </div>
                        </div>

                        {{-- Item Subtotal --}}
                        <p class="text-sm font-black flex-shrink-0" style="color: #2f342e;">
                            Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Rental Dates --}}
        <div class="px-4 py-2.5 flex flex-wrap items-center gap-3 text-[10px] font-semibold uppercase tracking-wider"
             style="border-bottom: 1px solid #f4f2ec; color: #7b776c;">
            <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-sm" style="color: #a09880;">event</span>
                Ambil: {{ \Carbon\Carbon::parse($trx->tanggal_ambil)->format('d M Y') }}
            </span>
            <span style="color: #ccc6b9;">→</span>
            <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-sm" style="color: #a09880;">event_available</span>
                Kembali: {{ \Carbon\Carbon::parse($trx->tanggal_kembali)->format('d M Y') }}
            </span>
        </div>

        {{-- Footer: Totals + Actions --}}
        <div class="flex items-center justify-between px-4 py-3">
            <div>
                <p class="text-[10px] uppercase tracking-wider font-semibold" style="color: #7b776c;">Total Sewa</p>
                <p class="text-base font-black" style="color: #2f342e;">Rp {{ number_format($trx->total_sewa, 0, ',', '.') }}</p>
                @if($trx->total_denda > 0)
                    <p class="text-[10px] font-semibold mt-0.5" style="color: #dc2626;">
                        + Denda Rp {{ number_format($trx->total_denda, 0, ',', '.') }}
                    </p>
                @endif
            </div>

            <div class="flex items-center gap-2">
                {{-- Bayar Denda --}}
                @php $dendaBelumBayar = $trx->denda->whereNull('dibayar_pada'); @endphp
                @if($dendaBelumBayar->count() > 0)
                    <a href="{{ route('user.pesanan.bayar-denda', $dendaBelumBayar->first()->id) }}"
                       class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg transition-all hover:opacity-80"
                       style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b;">
                        <span class="material-symbols-outlined text-sm">payment</span>
                        Bayar Denda
                    </a>
                @endif

                {{-- Lihat Struk (hanya jika bukan menunggu pembayaran) --}}
                @if(in_array($trx->status, ['dibayar', 'berjalan', 'terlambat', 'selesai']))
                    <a href="{{ route('user.pesanan.struk', $trx->id) }}"
                       class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg transition-all hover:opacity-80"
                       style="background: #f4f2ec; border: 1px solid rgba(101,94,68,0.2); color: #655e44;">
                        <span class="material-symbols-outlined text-sm">receipt_long</span>
                        Struk
                    </a>
                @endif

                {{-- Bayar / Detail --}}
                @if($trx->status === 'menunggu_pembayaran')
                    <a href="{{ route('user.pesanan.show', $trx->id) }}"
                       class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg transition-all hover:opacity-80 text-[#F2E8C6]"
                       style="background: #655e44;">
                        <span class="material-symbols-outlined text-sm">payments</span>
                        Bayar
                    </a>
                @else
                    <a href="{{ route('user.pesanan.show', $trx->id) }}"
                       class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg transition-all hover:opacity-80"
                       style="background: #fff; border: 1px solid rgba(101,94,68,0.22); color: #4d4a3e;">
                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                        Detail
                    </a>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="rounded-xl p-14 text-center" style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
        <span class="material-symbols-outlined text-5xl block mb-3" style="color: #ccc6b9;">inbox</span>
        <p class="text-sm font-semibold mb-1" style="color: #4d4a3e;">Tidak ada pesanan ditemukan</p>
        <p class="text-[10px] uppercase tracking-wider" style="color: #7b776c;">
            {{ request('search') ? 'Coba kata kunci lain' : 'Belum ada pesanan dengan status ini' }}
        </p>
        <a href="{{ url('/katalog') }}"
           class="inline-flex items-center gap-1.5 mt-5 text-[10px] font-black uppercase tracking-widest px-4 py-2.5 rounded-lg transition-colors text-[#F2E8C6] hover:opacity-90"
           style="background: #655e44;">
            <span class="material-symbols-outlined text-sm">explore</span>
            Mulai Sewa
        </a>
    </div>
@endforelse

{{-- Pagination --}}
@if($pesanan->hasPages())
    <div class="mt-6 flex items-center justify-center gap-1.5">
        @if($pesanan->onFirstPage())
            <span class="w-9 h-9 flex items-center justify-center rounded-lg cursor-not-allowed"
                  style="background: #fff; border: 1px solid rgba(101,94,68,0.15); color: #ccc6b9;">
                <span class="material-symbols-outlined text-base">chevron_left</span>
            </span>
        @else
            <a href="{{ $pesanan->previousPageUrl() }}"
               class="w-9 h-9 flex items-center justify-center rounded-lg transition-all hover:opacity-80"
               style="background: #fff; border: 1px solid rgba(101,94,68,0.2); color: #655e44;">
                <span class="material-symbols-outlined text-base">chevron_left</span>
            </a>
        @endif

        @foreach($pesanan->getUrlRange(1, $pesanan->lastPage()) as $page => $url)
            @if($page == $pesanan->currentPage())
                <span class="w-9 h-9 flex items-center justify-center rounded-lg text-xs font-black text-[#F2E8C6]"
                      style="background: #655e44;">{{ $page }}</span>
            @else
                <a href="{{ $url }}"
                   class="w-9 h-9 flex items-center justify-center rounded-lg text-xs font-semibold transition-all hover:opacity-80"
                   style="background: #fff; border: 1px solid rgba(101,94,68,0.2); color: #655e44;">
                    {{ $page }}
                </a>
            @endif
        @endforeach

        @if($pesanan->hasMorePages())
            <a href="{{ $pesanan->nextPageUrl() }}"
               class="w-9 h-9 flex items-center justify-center rounded-lg transition-all hover:opacity-80"
               style="background: #fff; border: 1px solid rgba(101,94,68,0.2); color: #655e44;">
                <span class="material-symbols-outlined text-base">chevron_right</span>
            </a>
        @else
            <span class="w-9 h-9 flex items-center justify-center rounded-lg cursor-not-allowed"
                  style="background: #fff; border: 1px solid rgba(101,94,68,0.15); color: #ccc6b9;">
                <span class="material-symbols-outlined text-base">chevron_right</span>
            </span>
        @endif
    </div>
@endif

@endsection
