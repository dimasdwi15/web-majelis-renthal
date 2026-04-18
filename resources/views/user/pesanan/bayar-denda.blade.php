@extends('user.layouts.user')

@section('title', 'Bayar Denda')

@section('content')

{{-- Back --}}
<a href="{{ route('user.pesanan.show', $denda->transaksi_id) }}"
   class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest mb-5 transition-colors group hover:opacity-70"
   style="color: #655e44;">
    <span class="material-symbols-outlined text-base group-hover:-translate-x-0.5 transition-transform">arrow_back</span>
    Kembali ke Detail Pesanan
</a>

<div class="max-w-lg mx-auto">

    {{-- Header --}}
    <div class="mb-6 text-center">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3"
             style="background: #fee2e2; border: 1px solid #fca5a5;">
            <span class="material-symbols-outlined text-2xl" style="color: #dc2626;">gavel</span>
        </div>
        <p class="text-[10px] uppercase tracking-[0.2em] font-bold mb-1" style="color: #a09880;">TAGIHAN DENDA</p>
        <h1 class="text-2xl font-black tracking-tight" style="color: #2f342e;">Pembayaran Denda</h1>
        <p class="text-xs mt-1" style="color: #7b776c;">{{ $denda->transaksi->nomor_transaksi }}</p>
    </div>

    {{-- Rincian Denda --}}
    <div class="rounded-xl overflow-hidden mb-4" style="background: #fff; border: 1px solid #fecaca; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
        <div class="px-4 py-3 flex items-center gap-2" style="background: #fef2f2; border-bottom: 1px solid #fecaca;">
            <span class="material-symbols-outlined text-base" style="color: #dc2626;">receipt</span>
            <p class="text-xs font-black uppercase tracking-widest" style="color: #991b1b;">Rincian Denda</p>
        </div>
        <div class="px-4 py-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div>
                    <p class="text-sm font-black capitalize mb-0.5" style="color: #2f342e;">
                        Denda {{ ucfirst($denda->jenis) }}
                    </p>
                    <p class="text-[10px] uppercase tracking-wider" style="color: #7b776c;">
                        Ditagihkan {{ $denda->created_at->format('d M Y, H:i') }}
                    </p>
                </div>
                <p class="text-xl font-black flex-shrink-0" style="color: #dc2626;">
                    Rp {{ number_format($denda->jumlah, 0, ',', '.') }}
                </p>
            </div>

            @if($denda->catatan)
                <div class="rounded-xl px-3 py-2.5 mb-3" style="background: #fef9f0; border: 1px solid #fde68a;">
                    <p class="text-[9px] uppercase tracking-widest font-semibold mb-1" style="color: #92400e;">Catatan dari Admin</p>
                    <p class="text-xs leading-relaxed" style="color: #78350f;">{{ $denda->catatan }}</p>
                </div>
            @endif

            @if($denda->foto->count() > 0)
                <div>
                    <p class="text-[9px] uppercase tracking-widest font-semibold mb-2" style="color: #a09880;">Bukti Kerusakan</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($denda->foto as $foto)
                            <a href="{{ Storage::url($foto->path_foto) }}" target="_blank"
                               class="w-20 h-20 rounded-xl overflow-hidden flex-shrink-0 group relative"
                               style="border: 1px solid rgba(101,94,68,0.2);">
                                <img src="{{ Storage::url($foto->path_foto) }}" alt="Bukti" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white text-lg">zoom_in</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Pilih Metode --}}
    <div x-data="{ metode: '{{ old('metode', 'midtrans') }}' }"
         class="rounded-xl overflow-hidden mb-4"
         style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">

        <div class="px-4 py-3 flex items-center gap-2" style="background: #f4f2ec; border-bottom: 1px solid rgba(101,94,68,0.08);">
            <span class="material-symbols-outlined text-base" style="color: #655e44;">payment</span>
            <p class="text-xs font-black uppercase tracking-widest" style="color: #2f342e;">Pilih Metode Pembayaran</p>
        </div>

        <div class="p-4 space-y-2.5">

            {{-- Midtrans --}}
            <label class="flex items-center gap-3 p-3.5 rounded-xl cursor-pointer transition-all"
                   :style="metode === 'midtrans'
                       ? 'background: #F2E8C6; border: 1.5px solid #655e44;'
                       : 'background: #faf9f5; border: 1px solid rgba(101,94,68,0.18);'">
                <input type="radio" name="metode" value="midtrans" x-model="metode" class="hidden">
                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                     :style="metode === 'midtrans' ? 'border-color: #655e44;' : 'border-color: #ccc6b9;'">
                    <div class="w-2 h-2 rounded-full transition-opacity"
                         :style="metode === 'midtrans' ? 'background: #655e44; opacity: 1;' : 'opacity: 0;'"></div>
                </div>
                <div class="flex items-center gap-2.5 flex-1">
                    <span class="material-symbols-outlined text-xl" style="color: #655e44;">credit_card</span>
                    <div>
                        <p class="text-xs font-bold" style="color: #2f342e;">Pembayaran Cashless</p>
                        <p class="text-[10px] uppercase tracking-wider" style="color: #7b776c;">Transfer · QRIS · E-Wallet via Midtrans</p>
                    </div>
                </div>
                <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-lg flex-shrink-0"
                      style="background: #F2E8C6; border: 1px solid rgba(101,94,68,0.3); color: #655e44;">
                    Rekomendasi
                </span>
            </label>

            {{-- Tunai --}}
            <label class="flex items-center gap-3 p-3.5 rounded-xl cursor-pointer transition-all"
                   :style="metode === 'tunai'
                       ? 'background: #F2E8C6; border: 1.5px solid #655e44;'
                       : 'background: #faf9f5; border: 1px solid rgba(101,94,68,0.18);'">
                <input type="radio" name="metode" value="tunai" x-model="metode" class="hidden">
                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                     :style="metode === 'tunai' ? 'border-color: #655e44;' : 'border-color: #ccc6b9;'">
                    <div class="w-2 h-2 rounded-full transition-opacity"
                         :style="metode === 'tunai' ? 'background: #655e44; opacity: 1;' : 'opacity: 0;'"></div>
                </div>
                <div class="flex items-center gap-2.5 flex-1">
                    <span class="material-symbols-outlined text-xl" style="color: #655e44;">payments</span>
                    <div>
                        <p class="text-xs font-bold" style="color: #2f342e;">Tunai ke Admin</p>
                        <p class="text-[10px] uppercase tracking-wider" style="color: #7b776c;">Bayar langsung di toko</p>
                    </div>
                </div>
            </label>
        </div>

        {{-- Info Tunai --}}
        <div x-show="metode === 'tunai'"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="px-4 pb-4">
            <div class="rounded-xl px-4 py-3" style="background: #fffbeb; border: 1px solid #fde68a;">
                <p class="text-[10px] font-black uppercase tracking-widest mb-1.5" style="color: #92400e;">
                    Instruksi Pembayaran Tunai
                </p>
                <p class="text-xs leading-relaxed" style="color: #78350f;">
                    Datang langsung ke toko dan tunjukkan nomor transaksi
                    <strong class="font-black">{{ $denda->transaksi->nomor_transaksi }}</strong>
                    kepada admin untuk menyelesaikan pembayaran denda.
                </p>
            </div>
        </div>

        {{-- Total & Tombol Bayar --}}
        <div class="px-4 pb-4">
            <div class="flex items-center justify-between py-3.5" style="border-top: 1px solid rgba(101,94,68,0.1); margin-bottom: 12px;">
                <p class="text-xs font-semibold uppercase tracking-wider" style="color: #7b776c;">Total Tagihan</p>
                <p class="text-xl font-black" style="color: #2f342e;">Rp {{ number_format($denda->jumlah, 0, ',', '.') }}</p>
            </div>

            {{-- Bayar Midtrans --}}
            <div x-show="metode === 'midtrans'" x-transition>
                @if($snapToken)
                    <button onclick="bayarDenda('{{ $denda->id }}')"
                            class="w-full flex items-center justify-center gap-2 text-[#F2E8C6] font-black text-sm uppercase tracking-widest py-3.5 rounded-xl transition-all hover:opacity-90 active:scale-[0.97]"
                            style="background: #655e44;">
                        <span class="material-symbols-outlined text-base">credit_card</span>
                        Bayar Sekarang via Midtrans
                    </button>
                @else
                    <form method="POST" action="{{ route('user.pesanan.proses-bayar-denda', $denda->id) }}">
                        @csrf
                        <input type="hidden" name="metode" value="midtrans">
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-2 text-[#F2E8C6] font-black text-sm uppercase tracking-widest py-3.5 rounded-xl transition-all hover:opacity-90 active:scale-[0.97]"
                                style="background: #655e44;">
                            <span class="material-symbols-outlined text-base">credit_card</span>
                            Lanjut ke Pembayaran
                        </button>
                    </form>
                @endif
            </div>

            {{-- Tunai --}}
            <div x-show="metode === 'tunai'" x-transition>
                <div class="rounded-xl px-4 py-4 text-center" style="background: #f4f2ec; border: 1px solid rgba(101,94,68,0.15);">
                    <span class="material-symbols-outlined text-2xl block mb-1" style="color: #7b776c;">store</span>
                    <p class="text-xs leading-relaxed" style="color: #7b776c;">
                        Silakan datang langsung ke toko untuk membayar denda secara tunai.
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function bayarDenda(dendaId) {
    fetch(`/akun/pesanan/bayar-denda/${dendaId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.snap_token) {
            snap.pay(data.snap_token, {
                onSuccess: function(result) {
                    window.location.href = '{{ route('user.pesanan.show', $denda->transaksi_id) }}';
                },
                onPending: function(result) {
                    window.location.reload();
                },
                onError: function(result) {
                    alert('Pembayaran gagal');
                }
            });
        }
    });
}
</script>
@endpush
