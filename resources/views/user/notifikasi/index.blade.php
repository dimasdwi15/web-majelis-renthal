@extends('user.layouts.user')

@section('title', 'Notifikasi')

@section('content')

{{-- ─── Header ──────────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-[10px] uppercase tracking-[0.2em] font-bold mb-1" style="color: #a09880;">PUSAT INFORMASI</p>
        <h1 class="text-2xl font-black tracking-tight" style="color: #2f342e;">Notifikasi</h1>
    </div>

    @if($notifikasi->whereNull('dibaca_pada')->count() > 0)
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

{{-- ─── Filter chips ────────────────────────────────────────────────────── --}}
<div class="flex gap-2 mb-5 overflow-x-auto scrollbar-hide pb-1">
    @php $unread = $notifikasi->whereNull('dibaca_pada')->count(); @endphp

    <span class="flex-shrink-0 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-lg"
          style="background: #655e44; color: #F2E8C6;">
        Semua ({{ $notifikasi->total() }})
    </span>

    @if($unread > 0)
        <span id="chip-unread"
              class="flex-shrink-0 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg"
              style="background: #fef3c7; border: 1px solid #fcd34d; color: #92400e;">
            Belum Dibaca (<span id="chip-unread-count">{{ $unread }}</span>)
        </span>
    @endif
</div>

{{-- ─── ✅ List container — diberi ID agar JS bisa prepend item real-time ─ --}}
<div id="notifikasi-list">

@forelse($notifikasi as $notif)

    <div class="mb-2 rounded-xl overflow-hidden transition-all duration-200"
         x-data="{ open: false }"
         id="notif-item-{{ $notif->id }}"
         style="background: #fff; box-shadow: 0 2px 8px rgba(37,29,29,0.06);
                {{ is_null($notif->dibaca_pada) ? 'border: 1.5px solid rgba(101,94,68,0.35);' : 'border: 1px solid rgba(101,94,68,0.1);' }}">

        <div class="flex items-start gap-3 px-4 py-3.5 cursor-pointer select-none" @click="open = !open">

            @php
                $notifIconBg    = $notif->tipe === 'denda' ? '#fee2e2' : ($notif->tipe === 'pembayaran' ? '#dcfce7' : '#F2E8C6');
                $notifIconColor = $notif->tipe === 'denda' ? '#dc2626' : ($notif->tipe === 'pembayaran' ? '#16a34a' : '#655e44');
                $notifIcon      = $notif->tipe === 'denda' ? 'gavel' : ($notif->tipe === 'pembayaran' ? 'check_circle' : 'notifications');
            @endphp

            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5"
                 style="background: {{ $notifIconBg }};">
                <span class="material-symbols-outlined text-base" style="color: {{ $notifIconColor }};">
                    {{ $notifIcon }}
                </span>
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs leading-snug {{ is_null($notif->dibaca_pada) ? 'font-black' : 'font-semibold' }}"
                       style="color: #2f342e;">
                        {{ $notif->judul }}
                    </p>

                    @if(is_null($notif->dibaca_pada))
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
                <p class="text-xs leading-relaxed mb-3" style="color: #4d4a3e;">
                    {{ $notif->pesan }}
                </p>

                <div class="flex flex-wrap items-center gap-2">

                    @if($notif->tipe === 'denda' && isset($notif->data['denda_id']))
                        <a href="{{ route('user.pesanan.bayar-denda', $notif->data['denda_id']) }}"
                           class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg text-white"
                           style="background: #dc2626;">
                            <span class="material-symbols-outlined text-sm">payment</span>
                            Bayar Denda
                        </a>
                    @endif

                    @if(is_null($notif->dibaca_pada))
                        <form method="POST" action="{{ route('user.notifikasi.baca', $notif->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="text-[10px] font-semibold uppercase tracking-wider flex items-center gap-1 py-2"
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

    {{-- Auto mark dibaca setelah 3 detik --}}
    @if(is_null($notif->dibaca_pada))
        @push('scripts')
        <script>
        setTimeout(function () {
            fetch('{{ route('user.notifikasi.baca', $notif->id) }}', {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                }
            }).catch(() => {});
        }, 3000);
        </script>
        @endpush
    @endif

@empty

    {{-- ✅ Beri id agar JS bisa hapus empty state saat notif baru masuk --}}
    <div id="notif-empty-state"
         class="rounded-xl p-14 text-center" style="background: #fff;">
        <span class="material-symbols-outlined text-5xl block mb-3" style="color: #ccc6b9;">
            notifications_off
        </span>
        <p class="text-sm font-semibold mb-1">Tidak ada notifikasi</p>
        <p class="text-[10px] uppercase tracking-wider">Semua notifikasi akan muncul di sini</p>
    </div>

@endforelse

</div>{{-- end #notifikasi-list --}}

@endsection

@push('scripts')
{{-- ═══════════════════════════════════════════════════════════════════════════
     REAL-TIME — inject notifikasi baru ke halaman tanpa reload
     Hanya berjalan di halaman notifikasi.
     ═══════════════════════════════════════════════════════════════════════════ --}}
<script>
(function () {
    'use strict';

    // Tunggu Echo siap
    function initEchoOnPage() {
        if (typeof window.Echo === 'undefined') {
            setTimeout(initEchoOnPage, 200);
            return;
        }
        listenNotifikasiPage();
    }

    function listenNotifikasiPage() {
        const userId = {{ Auth::id() }};

        window.Echo
            .private(`notifikasi.${userId}`)
            .listen('.notifikasi.masuk', function (payload) {
                prependNotifItem(payload);
                updateChipUnread();
            });
    }

    // ── Prepend card notif baru ke atas list ────────────────────────────
    function prependNotifItem(payload) {
        // Hapus empty state jika ada
        const emptyState = document.getElementById('notif-empty-state');
        if (emptyState) emptyState.remove();

        const list = document.getElementById('notifikasi-list');
        if (!list) return;

        const isDenda   = payload.tipe === 'denda';
        const iconBg    = isDenda ? '#fee2e2' : (payload.tipe === 'pembayaran' ? '#dcfce7' : '#F2E8C6');
        const iconColor = isDenda ? '#dc2626' : (payload.tipe === 'pembayaran' ? '#16a34a' : '#655e44');
        const iconName  = isDenda ? 'gavel'   : (payload.tipe === 'pembayaran' ? 'check_circle' : 'notifications');

        // Aksi bayar denda (jika ada denda_id di payload.data)
        const dendaId    = payload.data?.denda_id ?? null;
        const bayarBtn   = dendaId && isDenda
            ? `<a href="/akun/pesanan/bayar-denda/${dendaId}"
                  class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg text-white"
                  style="background: #dc2626;">
                    <span class="material-symbols-outlined text-sm">payment</span>
                    Bayar Denda
               </a>`
            : '';

        // Buat wrapper Alpine-compatible dengan template string
        const wrapper = document.createElement('div');
        wrapper.className = 'mb-2 rounded-xl overflow-hidden transition-all duration-200';
        wrapper.style.cssText = 'background:#fff; box-shadow:0 2px 8px rgba(37,29,29,0.06); border:1.5px solid rgba(101,94,68,0.35);';

        // Konten — tanpa Alpine karena tidak bisa init secara dinamis
        // menggunakan details/summary native sebagai alternatif collapsible
        wrapper.innerHTML = `
            <details class="group">
                <summary class="flex items-start gap-3 px-4 py-3.5 cursor-pointer select-none list-none">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5"
                         style="background: ${iconBg};">
                        <span class="material-symbols-outlined text-base" style="color: ${iconColor}; font-size:18px;">
                            ${iconName}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs leading-snug font-black" style="color: #2f342e;">
                                ${escapeHtml(payload.judul)}
                            </p>
                            <span class="w-2 h-2 rounded-full flex-shrink-0 mt-1"
                                  style="background:#655e44;"></span>
                        </div>
                        <p class="text-[10px] uppercase tracking-wider mt-0.5" style="color: #7b776c;">
                            Baru saja
                        </p>
                    </div>
                    <span class="material-symbols-outlined text-base flex-shrink-0 mt-0.5
                                 group-open:rotate-180 transition-transform"
                          style="color: #a09880;">expand_more</span>
                </summary>

                <div style="border-top: 1px solid rgba(101,94,68,0.08);">
                    <div class="px-4 py-3.5" style="background: #faf9f5;">
                        <p class="text-xs leading-relaxed mb-3" style="color: #4d4a3e;">
                            ${escapeHtml(payload.pesan)}
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            ${bayarBtn}
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-[#a09880]
                                         flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">schedule</span>
                                Belum dibaca
                            </span>
                        </div>
                    </div>
                </div>
            </details>
        `;

        // Animasi masuk
        wrapper.style.opacity = '0';
        wrapper.style.transform = 'translateY(-8px)';
        list.prepend(wrapper);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                wrapper.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                wrapper.style.opacity   = '1';
                wrapper.style.transform = 'translateY(0)';
            });
        });
    }

    // ── Update chip "Belum Dibaca" ───────────────────────────────────────
    function updateChipUnread() {
        const chip      = document.getElementById('chip-unread');
        const chipCount = document.getElementById('chip-unread-count');

        if (chip && chipCount) {
            const current = parseInt(chipCount.textContent) || 0;
            chipCount.textContent = current + 1;
        } else {
            // Chip belum ada (tadinya unread = 0), buat chip baru
            const filterBar = document.querySelector('.flex.gap-2.mb-5');
            if (!filterBar) return;

            const newChip = document.createElement('span');
            newChip.id        = 'chip-unread';
            newChip.className = 'flex-shrink-0 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg';
            newChip.style.cssText = 'background:#fef3c7; border:1px solid #fcd34d; color:#92400e;';
            newChip.innerHTML = 'Belum Dibaca (<span id="chip-unread-count">1</span>)';
            filterBar.appendChild(newChip);
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str ?? '')));
        return div.innerHTML;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEchoOnPage);
    } else {
        initEchoOnPage();
    }
})();
</script>
@endpush
