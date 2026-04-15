@extends('user.layouts.user')

@section('title', 'Notifikasi')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-[10px] uppercase tracking-[0.2em] font-bold mb-1" style="color: #a09880;">PUSAT INFORMASI</p>
        <h1 class="text-2xl font-black tracking-tight" style="color: #2f342e;">Notifikasi</h1>
    </div>
    @if($notifikasi->where('dibaca', false)->count() > 0)
        <form method="POST" action="{{ route('user.notifikasi.baca-semua') }}">
            @csrf
            <button type="submit"
                    class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg transition-all hover:opacity-80"
                    style="background: #F2E8C6; border: 1px solid rgba(101,94,68,0.25); color: #655e44;">
                <span class="material-symbols-outlined text-sm">done_all</span>
                Tandai Semua Dibaca
            </button>
        </form>
    @endif
</div>

{{-- Filter chips --}}
<div class="flex gap-2 mb-5 overflow-x-auto scrollbar-hide pb-1">
    @php $unread = $notifikasi->where('dibaca', false)->count(); @endphp
    <span class="flex-shrink-0 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-lg"
          style="background: #655e44; color: #F2E8C6;">
        Semua ({{ $notifikasi->total() }})
    </span>
    @if($unread > 0)
        <span class="flex-shrink-0 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg"
              style="background: #fef3c7; border: 1px solid #fcd34d; color: #92400e;">
            Belum Dibaca ({{ $unread }})
        </span>
    @endif
</div>

@forelse($notifikasi as $notif)
    <div class="mb-2 rounded-xl overflow-hidden transition-all duration-200"
         x-data="{ open: false }"
         style="background: #fff; box-shadow: 0 2px 8px rgba(37,29,29,0.06);
                {{ !$notif->dibaca ? 'border: 1.5px solid rgba(101,94,68,0.35);' : 'border: 1px solid rgba(101,94,68,0.1);' }}">

        <div class="flex items-start gap-3 px-4 py-3.5 cursor-pointer select-none" @click="open = !open">

            {{-- Icon --}}
            @php
                $notifIconBg    = $notif->tipe === 'denda' ? '#fee2e2' : ($notif->tipe === 'pembayaran' ? '#dcfce7' : '#F2E8C6');
                $notifIconColor = $notif->tipe === 'denda' ? '#dc2626' : ($notif->tipe === 'pembayaran' ? '#16a34a' : '#655e44');
                $notifIcon      = $notif->tipe === 'denda' ? 'gavel' : ($notif->tipe === 'pembayaran' ? 'check_circle' : 'notifications');
            @endphp
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5"
                 style="background: {{ $notifIconBg }};">
                <span class="material-symbols-outlined text-base" style="color: {{ $notifIconColor }};">{{ $notifIcon }}</span>
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs leading-snug {{ !$notif->dibaca ? 'font-black' : 'font-semibold' }}"
                       style="color: #2f342e;">
                        {{ $notif->judul }}
                    </p>
                    @if(!$notif->dibaca)
                        <span class="w-2 h-2 rounded-full flex-shrink-0 mt-1 pulse-live"
                              style="background: #655e44;"></span>
                    @endif
                </div>
                <p class="text-[10px] uppercase tracking-wider mt-0.5" style="color: #7b776c;">
                    {{ $notif->created_at->diffForHumans() }}
                </p>
            </div>

            <span class="material-symbols-outlined text-base flex-shrink-0 transition-transform mt-0.5"
                  :class="open ? 'rotate-180' : ''"
                  style="color: #a09880;">
                expand_more
            </span>
        </div>

        {{-- Expanded Content --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="border-top: 1px solid rgba(101,94,68,0.08);">
            <div class="px-4 py-3.5" style="background: #faf9f5;">
                <p class="text-xs leading-relaxed mb-3" style="color: #4d4a3e;">{{ $notif->pesan }}</p>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center gap-2">
                    @if($notif->tipe === 'denda' && isset($notif->data['denda_id']))
                        <a href="{{ route('user.pesanan.bayar-denda', $notif->data['denda_id']) }}"
                           class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg transition-all hover:opacity-80 text-white"
                           style="background: #dc2626;">
                            <span class="material-symbols-outlined text-sm">payment</span>
                            Bayar Denda
                        </a>
                        @if(isset($notif->data['transaksi_id']))
                            <a href="{{ route('user.pesanan.show', $notif->data['transaksi_id']) }}"
                               class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg transition-all hover:opacity-80"
                               style="background: #f4f2ec; border: 1px solid rgba(101,94,68,0.2); color: #655e44;">
                                <span class="material-symbols-outlined text-sm">open_in_new</span>
                                Lihat Pesanan
                            </a>
                        @endif
                    @elseif($notif->tipe === 'pembayaran' && isset($notif->data['transaksi_id']))
                        <a href="{{ route('user.pesanan.show', $notif->data['transaksi_id']) }}"
                           class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg transition-all hover:opacity-80"
                           style="background: #dcfce7; border: 1px solid #86efac; color: #166534;">
                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                            Lihat Detail Pesanan
                        </a>
                    @elseif(isset($notif->data['transaksi_id']))
                        <a href="{{ route('user.pesanan.show', $notif->data['transaksi_id']) }}"
                           class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg transition-all hover:opacity-80"
                           style="background: #f4f2ec; border: 1px solid rgba(101,94,68,0.2); color: #655e44;">
                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                            Lihat Pesanan
                        </a>
                    @endif

                    {{-- Mark as read --}}
                    @if(!$notif->dibaca)
                        <form method="POST" action="{{ route('user.notifikasi.baca', $notif->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="text-[10px] font-semibold uppercase tracking-wider transition-colors flex items-center gap-1 py-2"
                                    style="color: #7b776c;">
                                <span class="material-symbols-outlined text-sm">done</span>
                                Tandai Dibaca
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Auto mark as read --}}
    @if(!$notif->dibaca)
        @push('scripts')
        <script>
        setTimeout(function() {
            fetch('{{ route('user.notifikasi.baca', $notif->id) }}', {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            }).catch(() => {});
        }, 3000);
        </script>
        @endpush
    @endif
@empty
    <div class="rounded-xl p-14 text-center" style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
        <span class="material-symbols-outlined text-5xl block mb-3" style="color: #ccc6b9;">notifications_off</span>
        <p class="text-sm font-semibold mb-1" style="color: #4d4a3e;">Tidak ada notifikasi</p>
        <p class="text-[10px] uppercase tracking-wider" style="color: #7b776c;">Semua notifikasi akan muncul di sini</p>
    </div>
@endforelse

{{-- Pagination --}}
@if($notifikasi->hasPages())
    <div class="mt-6 flex items-center justify-center gap-1.5">
        @if($notifikasi->onFirstPage())
            <span class="w-9 h-9 flex items-center justify-center rounded-lg cursor-not-allowed"
                  style="background: #fff; border: 1px solid rgba(101,94,68,0.12); color: #ccc6b9;">
                <span class="material-symbols-outlined text-base">chevron_left</span>
            </span>
        @else
            <a href="{{ $notifikasi->previousPageUrl() }}"
               class="w-9 h-9 flex items-center justify-center rounded-lg transition-all hover:opacity-80"
               style="background: #fff; border: 1px solid rgba(101,94,68,0.2); color: #655e44;">
                <span class="material-symbols-outlined text-base">chevron_left</span>
            </a>
        @endif

        @foreach($notifikasi->getUrlRange(1, $notifikasi->lastPage()) as $page => $url)
            @if($page == $notifikasi->currentPage())
                <span class="w-9 h-9 flex items-center justify-center rounded-lg text-xs font-black"
                      style="background: #655e44; color: #F2E8C6;">{{ $page }}</span>
            @else
                <a href="{{ $url }}"
                   class="w-9 h-9 flex items-center justify-center rounded-lg text-xs font-semibold transition-all hover:opacity-80"
                   style="background: #fff; border: 1px solid rgba(101,94,68,0.2); color: #655e44;">
                    {{ $page }}
                </a>
            @endif
        @endforeach

        @if($notifikasi->hasMorePages())
            <a href="{{ $notifikasi->nextPageUrl() }}"
               class="w-9 h-9 flex items-center justify-center rounded-lg transition-all hover:opacity-80"
               style="background: #fff; border: 1px solid rgba(101,94,68,0.2); color: #655e44;">
                <span class="material-symbols-outlined text-base">chevron_right</span>
            </a>
        @else
            <span class="w-9 h-9 flex items-center justify-center rounded-lg cursor-not-allowed"
                  style="background: #fff; border: 1px solid rgba(101,94,68,0.12); color: #ccc6b9;">
                <span class="material-symbols-outlined text-base">chevron_right</span>
            </span>
        @endif
    </div>
@endif

@endsection
