@extends('user.layouts.user')

@section('title', 'Detail Pesanan ' . $transaksi->nomor_transaksi)

@section('content')

    @php
        use App\Enums\StatusTransaksi;
        $statusValue =
            $transaksi->status instanceof StatusTransaksi ? $transaksi->status->value : (string) $transaksi->status;
        $metodeValue =
            $transaksi->metode_pembayaran instanceof \App\Enums\MetodePembayaran
                ? $transaksi->metode_pembayaran->value
                : (string) $transaksi->metode_pembayaran;
    @endphp

    {{-- Back Button --}}
    <a href="{{ route('user.pesanan.index') }}"
        class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest mb-5 transition-colors group hover:opacity-70"
        style="color: #655e44;">
        <span class="material-symbols-outlined text-base group-hover:-translate-x-0.5 transition-transform">arrow_back</span>
        Kembali ke Pesanan
    </a>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-6">
        <div>
            <p class="text-[10px] uppercase tracking-[0.2em] font-bold mb-1" style="color: #a09880;">DETAIL PESANAN</p>
            <h1 class="text-2xl font-black tracking-tight" style="color: #2f342e;">{{ $transaksi->nomor_transaksi }}</h1>
            <p class="text-[10px] uppercase tracking-wider mt-1" style="color: #7b776c;">
                Dibuat {{ $transaksi->created_at->format('d M Y, H:i') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @include('user.components.status-badge', ['status' => $transaksi->status])
            {{-- Tombol Struk --}}
            @if (in_array($statusValue, ['dibayar', 'berjalan', 'terlambat', 'dikembalikan', 'selesai']))
                <a href="{{ route('user.pesanan.struk', $transaksi->id) }}"
                    class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg transition-all hover:opacity-80"
                    style="background: #F2E8C6; border: 1px solid rgba(101,94,68,0.3); color: #655e44;">
                    <span class="material-symbols-outlined text-sm">receipt_long</span>
                    Lihat Struk
                </a>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">

        {{-- LEFT: Main Info --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Barang Disewa --}}
            <div class="rounded-xl overflow-hidden" style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
                <div class="px-4 py-3 flex items-center gap-2"
                    style="background: #f4f2ec; border-bottom: 1px solid rgba(101,94,68,0.08);">
                    <span class="material-symbols-outlined text-base" style="color: #655e44;">inventory_2</span>
                    <p class="text-xs font-black uppercase tracking-widest" style="color: #2f342e;">Barang Disewa</p>
                </div>
                <div>
                    @foreach ($transaksi->details as $detail)
                        <div class="flex items-center gap-3 px-4 py-3.5" style="border-bottom: 1px solid #f4f2ec;">
                            {{-- Image --}}
                            <div class="w-14 h-14 rounded-xl overflow-hidden flex-shrink-0" style="background: #f4f2ec;">
                                @if ($detail->barang->fotoUtama)
                                    <img src="{{ Storage::url($detail->barang->fotoUtama->path_foto) }}"
                                        alt="{{ $detail->barang->nama }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <span class="material-symbols-outlined text-xl"
                                            style="color: #ccc6b9;">image_not_supported</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold truncate" style="color: #2f342e;">{{ $detail->barang->nama }}
                                </p>
                                <p class="text-[10px] uppercase tracking-wider mt-0.5" style="color: #7b776c;">
                                    {{ $detail->jumlah }} × Rp
                                    {{ number_format($detail->harga_per_hari, 0, ',', '.') }}/hari
                                    × {{ $detail->durasi_hari }} hari
                                </p>
                            </div>
                            <p class="text-sm font-black flex-shrink-0" style="color: #2f342e;">
                                Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                            </p>
                        </div>
                    @endforeach
                </div>

                {{-- Subtotals --}}
                <div class="px-4 py-3.5 space-y-2">
                    <div class="flex justify-between text-xs">
                        <span class="uppercase tracking-wider font-semibold" style="color: #7b776c;">Total Sewa</span>
                        <span class="font-bold" style="color: #2f342e;">
                            Rp {{ number_format($transaksi->total_sewa, 0, ',', '.') }}
                        </span>
                    </div>

                    @if ($transaksi->total_denda > 0)
                        <div class="flex justify-between text-xs">
                            <span class="uppercase tracking-wider font-semibold" style="color: #dc2626;">Total Denda</span>
                            <span class="font-bold" style="color: #dc2626;">
                                + Rp {{ number_format($transaksi->total_denda, 0, ',', '.') }}
                            </span>
                        </div>
                    @endif

                    <div class="flex justify-between pt-2" style="border-top: 1px solid rgba(101,94,68,0.1);">
                        <span class="text-sm font-black uppercase tracking-wider" style="color: #2f342e;">
                            Grand Total
                        </span>
                        <span class="text-base font-black" style="color: #2f342e;">
                            Rp
                            {{ number_format($transaksi->total_sewa + $transaksi->total_denda, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Jadwal --}}
            <div class="rounded-xl overflow-hidden" style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
                <div class="px-4 py-3 flex items-center gap-2"
                    style="background: #f4f2ec; border-bottom: 1px solid rgba(101,94,68,0.08);">
                    <span class="material-symbols-outlined text-base" style="color: #655e44;">calendar_month</span>
                    <p class="text-xs font-black uppercase tracking-widest" style="color: #2f342e;">Jadwal Sewa</p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3">
                    <div class="px-4 py-3.5" style="border-right: 1px solid #f4f2ec;">
                        <p class="text-[9px] uppercase tracking-widest font-semibold mb-1" style="color: #a09880;">Tanggal
                            Ambil</p>
                        <p class="text-sm font-black" style="color: #2f342e;">
                            {{ \Carbon\Carbon::parse($transaksi->tanggal_ambil)->format('d M Y') }}</p>
                    </div>
                    <div class="px-4 py-3.5" style="border-right: 1px solid #f4f2ec;">
                        <p class="text-[9px] uppercase tracking-widest font-semibold mb-1" style="color: #a09880;">Batas
                            Kembali</p>
                        <p class="text-sm font-black" style="color: #2f342e;">
                            {{ \Carbon\Carbon::parse($transaksi->tanggal_kembali)->format('d M Y') }}</p>
                    </div>
                    @if ($transaksi->tanggal_dikembalikan)
                        <div class="px-4 py-3.5">
                            <p class="text-[9px] uppercase tracking-widest font-semibold mb-1" style="color: #a09880;">
                                Dikembalikan</p>
                            <p class="text-sm font-black" style="color: #2f342e;">
                                {{ \Carbon\Carbon::parse($transaksi->tanggal_dikembalikan)->format('d M Y, H:i') }}
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Countdown --}}
                @if (in_array($statusValue, ['dibayar', 'berjalan']))
                    @php
                        $deadline = \Carbon\Carbon::parse($transaksi->tanggal_kembali)->endOfDay();
                        $now = now();
                        $sisa = $now->diff($deadline);
                        $isLate = $now->gt($deadline);
                    @endphp
                    <div class="px-4 py-3"
                        style="border-top: 1px solid #f4f2ec; background: {{ $isLate ? '#fef2f2' : '#fafaf7' }};">
                        <p class="text-[9px] uppercase tracking-widest font-semibold mb-1"
                            style="color: {{ $isLate ? '#dc2626' : '#7b776c' }};">
                            {{ $isLate ? '⚠ TERLAMBAT' : 'SISA WAKTU' }}
                        </p>
                        <p class="text-sm font-black" style="color: {{ $isLate ? '#dc2626' : '#2f342e' }};">
                            @if ($isLate)
                                {{ $sisa->days }} hari {{ $sisa->h }} jam terlambat
                            @else
                                {{ $sisa->days }} hari {{ $sisa->h }} jam {{ $sisa->i }} menit lagi
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            {{-- Riwayat Pembayaran --}}
            <div class="rounded-xl overflow-hidden" style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
                <div class="px-4 py-3 flex items-center gap-2"
                    style="background: #f4f2ec; border-bottom: 1px solid rgba(101,94,68,0.08);">
                    <span class="material-symbols-outlined text-base" style="color: #655e44;">receipt_long</span>
                    <p class="text-xs font-black uppercase tracking-widest" style="color: #2f342e;">Riwayat Pembayaran</p>
                </div>
                @forelse($transaksi->pembayaran as $bayar)
                    @php
                        $payColor = match ($bayar->status) {
                            'lunas' => '#166534',
                            'gagal' => '#991b1b',
                            default => '#92400e',
                        };
                        $payBg = match ($bayar->status) {
                            'lunas' => '#dcfce7',
                            'gagal' => '#fee2e2',
                            default => '#fef3c7',
                        };
                        $payIcon = match ($bayar->status) {
                            'lunas' => 'check_circle',
                            'gagal' => 'cancel',
                            default => 'hourglass_empty',
                        };
                    @endphp
                    <div class="flex items-center gap-3 px-4 py-3.5" style="border-bottom: 1px solid #f4f2ec;">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                            style="background: {{ $payBg }};">
                            <span class="material-symbols-outlined text-sm"
                                style="color: {{ $payColor }};">{{ $payIcon }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold capitalize" style="color: #4d4a3e;">
                                Pembayaran {{ ucfirst($bayar->jenis) }} · {{ strtoupper($bayar->metode) }}
                            </p>
                            <p class="text-[10px] uppercase tracking-wider" style="color: #7b776c;">
                                {{ $bayar->dibayar_pada ? \Carbon\Carbon::parse($bayar->dibayar_pada)->format('d M Y, H:i') : 'Belum dibayar' }}
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-black" style="color: #2f342e;">Rp
                                {{ number_format($bayar->jumlah, 0, ',', '.') }}</p>
                            <p class="text-[10px] font-black uppercase" style="color: {{ $payColor }};">
                                {{ ucfirst($bayar->status) }}</p>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center">
                        <p class="text-xs uppercase tracking-wider" style="color: #7b776c;">Belum ada pembayaran</p>
                    </div>
                @endforelse
            </div>

            {{-- Denda --}}
            {{-- Denda --}}
            @if ($transaksi->denda->count() > 0)
                <div class="rounded-xl overflow-hidden"
                    style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07); border: 1px solid #fecaca;">

                    <div class="px-4 py-3 flex items-center gap-2"
                        style="background: #fef2f2; border-bottom: 1px solid #fecaca;">
                        <span class="material-symbols-outlined text-base" style="color: #dc2626;">gavel</span>
                        <p class="text-xs font-black uppercase tracking-widest" style="color: #991b1b;">
                            Tagihan Denda
                        </p>
                    </div>

                    @foreach ($transaksi->denda as $denda)
                        <div class="px-4 py-4" style="border-bottom: 1px solid #fff0f0;">

                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div>
                                    <p class="text-sm font-black capitalize mb-0.5" style="color: #2f342e;">
                                        Denda {{ ucfirst($denda->jenis) }}
                                    </p>
                                    <p class="text-[10px] uppercase tracking-wider" style="color: #7b776c;">
                                        Ditambahkan {{ $denda->created_at->format('d M Y, H:i') }}
                                    </p>
                                </div>

                                <div class="text-right flex-shrink-0">
                                    <p class="text-lg font-black" style="color: #dc2626;">
                                        Rp {{ number_format($denda->jumlah, 0, ',', '.') }}
                                    </p>

                                    @if ($denda->dibayar_pada)
                                        <span
                                            class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-lg"
                                            style="background: #dcfce7; color: #166534;">
                                            <span class="material-symbols-outlined text-sm">check</span> LUNAS
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1.5 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-lg"
                                            style="background: #fee2e2; color: #991b1b;">
                                            <span class="w-1.5 h-1.5 rounded-full pulse-live"
                                                style="background: #dc2626;"></span>
                                            BELUM LUNAS
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Catatan --}}
                            @if ($denda->catatan)
                                <div class="rounded-lg px-3 py-2 mb-3"
                                    style="background: #fef9f0; border: 1px solid #fde68a;">
                                    <p class="text-[9px] uppercase tracking-widest font-semibold mb-0.5"
                                        style="color: #92400e;">Catatan Admin</p>
                                    <p class="text-xs" style="color: #78350f;">
                                        {{ $denda->catatan }}
                                    </p>
                                </div>
                            @endif

                            {{-- Foto --}}
                            @if ($denda->foto->count() > 0)
                                <div class="mb-3">
                                    <p class="text-[9px] uppercase tracking-widest font-semibold mb-2"
                                        style="color: #a09880;">Bukti Foto Kerusakan</p>

                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($denda->foto as $foto)
                                            <a href="{{ Storage::url($foto->path_foto) }}" target="_blank"
                                                class="w-16 h-16 rounded-xl overflow-hidden flex-shrink-0 group relative"
                                                style="border: 1px solid rgba(101,94,68,0.2);">
                                                <img src="{{ Storage::url($foto->path_foto) }}"
                                                    class="w-full h-full object-cover">
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- ❌ BUTTON DIHAPUS DI SINI --}}

                        </div>
                    @endforeach
                </div>
            @endif

        </div>

        {{-- RIGHT: Sidebar --}}
        <div class="space-y-4">

            {{-- Info Pesanan --}}
            <div class="rounded-xl overflow-hidden" style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
                <div class="px-4 py-3" style="background: #f4f2ec; border-bottom: 1px solid rgba(101,94,68,0.08);">
                    <p class="text-xs font-black uppercase tracking-widest" style="color: #2f342e;">Info Pesanan</p>
                </div>
                <div class="px-4 py-3 space-y-3.5">
                    <div>
                        <p class="text-[9px] uppercase tracking-widest font-semibold mb-0.5" style="color: #a09880;">
                            Metode Pembayaran</p>
                        <p class="text-xs font-bold flex items-center gap-1.5" style="color: #2f342e;">
                            <span class="material-symbols-outlined text-sm" style="color: #655e44;">
                                {{ $metodeValue === 'midtrans' ? 'credit_card' : 'payments' }}
                            </span>
                            {{ strtoupper($metodeValue) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[9px] uppercase tracking-widest font-semibold mb-0.5" style="color: #a09880;">
                            Status Pembayaran</p>
                        @php
                            $spColor = match ($transaksi->status_pembayaran) {
                                'lunas' => '#166534',
                                'gagal' => '#991b1b',
                                'parsial' => '#1e40af',
                                default => '#92400e',
                            };
                        @endphp
                        <p class="text-xs font-black uppercase tracking-wider" style="color: {{ $spColor }};">
                            {{ ucfirst($transaksi->status_pembayaran) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Jaminan Identitas --}}
            @if ($transaksi->jaminanIdentitas)
                <div class="rounded-xl overflow-hidden"
                    style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
                    <div class="px-4 py-3" style="background: #f4f2ec; border-bottom: 1px solid rgba(101,94,68,0.08);">
                        <p class="text-xs font-black uppercase tracking-widest" style="color: #2f342e;">Jaminan Identitas
                        </p>
                    </div>
                    <div class="px-4 py-3" style="border-bottom: 1px solid #f4f2ec;">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="material-symbols-outlined text-base" style="color: #655e44;">badge</span>
                            <p class="text-xs font-semibold" style="color: #4d4a3e;">
                                {{ $transaksi->jaminanIdentitas->jenis_identitas }}</p>
                        </div>
                        @if ($transaksi->jaminanIdentitas->status === 'dihapus')
                            <p class="text-[10px] uppercase tracking-wider pl-6" style="color: #7b776c;">File sudah
                                dihapus</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- QR Code --}}
            @if (isset($transaksi->qr_code) && $transaksi->qr_code)
                <div class="rounded-xl overflow-hidden"
                    style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
                    <div class="px-4 py-3" style="background: #f4f2ec; border-bottom: 1px solid rgba(101,94,68,0.08);">
                        <p class="text-xs font-black uppercase tracking-widest" style="color: #2f342e;">QR Code
                            Pengembalian</p>
                    </div>
                    <div class="p-4 flex items-center justify-center">
                        <div class="bg-white p-2 rounded-xl" style="border: 1px solid rgba(101,94,68,0.15);">
                            {!! $transaksi->qr_code !!}
                        </div>
                    </div>
                    <div class="px-4 pb-4 text-center">
                        <p class="text-[9px] uppercase tracking-widest" style="color: #7b776c;">Tunjukkan ke admin saat
                            pengembalian</p>
                    </div>
                </div>
            @endif

            {{-- CTA Bayar --}}
            {{-- CTA: Menunggu Pembayaran --}}
            @if ($statusValue === 'menunggu_pembayaran')
                <div class="rounded-xl p-4" style="background: #fef3c7; border: 1px solid #fcd34d;">
                    <p class="text-xs font-black uppercase tracking-widest mb-1" style="color: #92400e;">Menunggu
                        Pembayaran</p>
                    <p class="text-xs mb-3 leading-relaxed" style="color: #78350f;">Segera selesaikan pembayaran sebelum
                        pesanan dibatalkan.</p>
                    @if ($metodeValue === 'midtrans')
                        @php
                            $pembayaranUtama = $transaksi->pembayaran
                                ->where('jenis', 'utama')
                                ->where('status', 'menunggu')
                                ->first();
                        @endphp
                        @if ($pembayaranUtama && $pembayaranUtama->referensi_midtrans)
                            <button onclick="bayarUlang('{{ $transaksi->id }}')"
                                class="w-full flex items-center justify-center gap-2 text-[#F2E8C6] font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all active:scale-[0.97] hover:opacity-90"
                                style="background: #655e44;">
                                <span class="material-symbols-outlined text-base">credit_card</span>
                                Bayar via Midtrans
                            </button>
                        @endif
                    @else
                        <div class="rounded-xl px-4 py-3 text-center"
                            style="background: #fff8e1; border: 1px solid rgba(101,94,68,0.2);">
                            <span class="material-symbols-outlined text-2xl block mb-1"
                                style="color: #655e44;">store</span>
                            <p class="text-[10px] uppercase tracking-wider" style="color: #7b776c;">Bayar tunai ke admin
                            </p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- CTA: Dikembalikan — ada tagihan denda --}}
            @if ($statusValue === 'dikembalikan' && $transaksi->total_denda > 0)
                <div class="rounded-xl p-4" style="background: #fff7ed; border: 1px solid #fdba74;">
                    <p class="text-xs font-black uppercase tracking-widest mb-1" style="color: #9a3412;">Barang
                        Dikembalikan</p>
                    <p class="text-xs mb-2 leading-relaxed" style="color: #c2410c;">Terdapat tagihan denda yang perlu
                        diselesaikan.</p>
                    <div class="flex justify-between items-center mb-3 px-3 py-2 rounded-lg" style="background: #fff;">
                        <span class="text-xs font-semibold" style="color: #7b776c;">Total Denda</span>
                        <span class="text-base font-black" style="color: #dc2626;">Rp
                            {{ number_format($transaksi->total_denda, 0, ',', '.') }}</span>
                    </div>
                    @php
                        $dendaBelumBayar = $transaksi->denda->whereNull('dibayar_pada')->first();
                    @endphp
                    @if ($dendaBelumBayar)
                        <a href="{{ route('user.pesanan.bayar-denda', $dendaBelumBayar->id) }}"
                            class="w-full flex items-center justify-center gap-2 text-white font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all active:scale-[0.97] hover:opacity-90"
                            style="background: #dc2626;">
                            <span class="material-symbols-outlined text-base">payment</span>
                            Bayar Denda Sekarang
                        </a>
                    @endif
                </div>
            @endif

            {{-- Lihat Struk Button --}}
            @if (in_array($statusValue, ['dibayar', 'berjalan', 'terlambat', 'dikembalikan', 'selesai']))
                <a href="{{ route('user.pesanan.struk', $transaksi->id) }}"
                    class="flex items-center justify-center gap-2 w-full text-[10px] font-black uppercase tracking-widest px-4 py-3 rounded-xl transition-all hover:opacity-80"
                    style="background: #F2E8C6; border: 1px solid rgba(101,94,68,0.3); color: #4d462e;">
                    <span class="material-symbols-outlined text-base">receipt_long</span>
                    Cetak / Lihat Struk
                </a>
            @endif

        </div>
    </div>

@endsection

@push('scripts')
<script>
    function bayarUlang(transaksiId) {
        fetch(`/pesanan/${transaksiId}/bayar-ulang`, {
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
                        window.location.reload();
                    },
                    onPending: function(result) {
                        window.location.reload();
                    },
                    onError: function(result) {
                        alert('Pembayaran gagal');
                    }
                });
            } else {
                alert('Gagal mendapatkan token pembayaran');
            }
        });
    }
</script>
@endpush
