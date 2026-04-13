<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Pemesanan Berhasil | MAJELIS RENTAL</title>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">

    {{-- QR Code library (CDN) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>

<body class="bg-[#f4f4f0] font-body">

    @include('user.components.navbar')

    <div class="max-w-2xl mx-auto px-4 py-12">

        {{-- ── STATUS HEADER ── --}}
        <div class="text-center mb-8">
            @if ($transaksi->metode_pembayaran === 'midtrans')
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-amber-600">pending</span>
                </div>
                <h1 class="text-3xl font-black uppercase tracking-tighter text-[#1b1c1a]">Menunggu Pembayaran</h1>
                <p class="text-sm text-[#7b776c] mt-1">Selesaikan pembayaran untuk mengkonfirmasi pesanan Anda</p>
            @else
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-green-600">check_circle</span>
                </div>
                <h1 class="text-3xl font-black uppercase tracking-tighter text-[#1b1c1a]">Pemesanan Diterima</h1>
                <p class="text-sm text-[#7b776c] mt-1">Tunjukkan surat ini saat pengambilan barang di toko</p>
            @endif
        </div>

        {{-- ── STRUK / SURAT PEMESANAN ── --}}
        <div id="struk" class="bg-white border border-[#ccc6b9]/60 rounded-lg overflow-hidden print:shadow-none">

            {{-- Header struk --}}
            <div class="bg-[#251D1D] text-[#F2E8C6] px-8 py-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[9px] uppercase tracking-[0.3em] opacity-60 mb-1">Majelis Rental</p>
                        <p class="font-black text-lg uppercase tracking-tight">
                            @if ($transaksi->metode_pembayaran === 'midtrans')
                                Struk Pembayaran
                            @else
                                Surat Pemesanan
                            @endif
                        </p>
                        <p class="text-[10px] opacity-60 mt-0.5">
                            @if ($transaksi->metode_pembayaran === 'tunai')
                                Bayar tunai di toko saat pengambilan
                            @else
                                Selesaikan pembayaran sebelum expired
                            @endif
                        </p>
                    </div>
                    {{-- QR Code atau Logo --}}
                    <div class="flex-shrink-0">
                        <div id="qrcode" class="bg-white p-1 rounded"></div>
                    </div>
                </div>
            </div>

            {{-- Nomor transaksi besar --}}
            <div class="px-8 py-4 bg-[#faf9f5] border-b border-[#ccc6b9]/40 text-center">
                <p class="text-[9px] text-[#7b776c] uppercase tracking-widest mb-1">Nomor Transaksi</p>
                <p class="text-2xl font-black tracking-wider text-[#4d462e]">{{ $transaksi->nomor_transaksi }}</p>
            </div>

            {{-- Info transaksi --}}
            <div class="px-8 py-5 grid grid-cols-2 gap-4 text-sm border-b border-[#ccc6b9]/40">
                <div>
                    <p class="text-[9px] text-[#7b776c] uppercase tracking-widest mb-0.5">Nama Penyewa</p>
                    <p class="font-semibold text-[#1b1c1a]">{{ Auth::user()->name }}</p>
                </div>
                <div>
                    <p class="text-[9px] text-[#7b776c] uppercase tracking-widest mb-0.5">Tanggal Pesan</p>
                    <p class="font-semibold text-[#1b1c1a]">{{ $transaksi->created_at->format('d M Y, H:i') }}</p>
                </div>
                <div>
                    <p class="text-[9px] text-[#7b776c] uppercase tracking-widest mb-0.5">Tanggal Ambil</p>
                    <p class="font-semibold text-[#1b1c1a]">
                        {{ \Carbon\Carbon::parse($transaksi->tanggal_ambil)->format('d M Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-[9px] text-[#7b776c] uppercase tracking-widest mb-0.5">Tanggal Kembali</p>
                    <p class="font-semibold text-[#1b1c1a]">
                        {{ \Carbon\Carbon::parse($transaksi->tanggal_kembali)->format('d M Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-[9px] text-[#7b776c] uppercase tracking-widest mb-0.5">Durasi</p>
                    <p class="font-semibold text-[#1b1c1a]">
                        {{ \Carbon\Carbon::parse($transaksi->tanggal_ambil)->diffInDays($transaksi->tanggal_kembali) }} Hari
                    </p>
                </div>
                <div>
                    <p class="text-[9px] text-[#7b776c] uppercase tracking-widest mb-0.5">Metode Bayar</p>
                    <p class="font-semibold text-[#1b1c1a]">
                        {{ $transaksi->metode_pembayaran === 'midtrans' ? 'Cashless (Midtrans)' : 'Tunai di Toko' }}
                    </p>
                </div>
            </div>

            {{-- Daftar barang --}}
            <div class="px-8 py-5 border-b border-[#ccc6b9]/40">
                <p class="text-[9px] text-[#7b776c] uppercase tracking-widest mb-3">Detail Barang Sewa</p>
                <div class="space-y-3">
                    @foreach ($transaksi->details as $detail)
                        <div class="flex justify-between items-start text-sm">
                            <div>
                                <p class="font-semibold text-[#1b1c1a] uppercase">{{ $detail->barang->nama ?? '—' }}</p>
                                <p class="text-[10px] text-[#7b776c]">
                                    {{ $detail->jumlah }} unit
                                    × Rp {{ number_format($detail->harga_per_hari, 0, ',', '.') }}/hari
                                    × {{ $detail->durasi_hari }} hari
                                </p>
                            </div>
                            <span class="font-bold text-[#4d462e]">
                                Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Total --}}
            <div class="px-8 py-5 border-b border-[#ccc6b9]/40">
                <div class="flex justify-between items-baseline">
                    <span class="text-[10px] font-bold text-[#7b776c] uppercase tracking-widest">Total Sewa</span>
                    <span class="text-3xl font-black text-[#4d462e]">
                        Rp {{ number_format($transaksi->total_sewa, 0, ',', '.') }}
                    </span>
                </div>
                @if ($transaksi->metode_pembayaran === 'tunai')
                    <p class="text-[10px] text-amber-700 mt-1">
                        * Bayar tunai saat pengambilan barang di toko
                    </p>
                @endif
            </div>

            {{-- Info tambahan berdasarkan metode --}}
            @if ($transaksi->metode_pembayaran === 'tunai')
                {{-- COD: instruksi ke toko --}}
                <div class="px-8 py-5 bg-amber-50 border-b border-amber-200">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-xl text-amber-600 flex-shrink-0">storefront</span>
                        <div class="text-xs text-amber-800 space-y-1.5">
                            <p class="font-bold uppercase tracking-wider">Instruksi Pengambilan (COD)</p>
                            <ol class="list-decimal list-inside space-y-1 leading-relaxed">
                                <li>Datang ke toko pada <strong>{{ \Carbon\Carbon::parse($transaksi->tanggal_ambil)->format('d M Y') }}</strong></li>
                                <li>Tunjukkan <strong>nomor transaksi</strong> atau QR Code di atas kepada admin</li>
                                <li>Serahkan <strong>kartu identitas fisik</strong> ({{ $transaksi->jaminanIdentitas->jenis_identitas ?? 'KTP' }}) sebagai jaminan</li>
                                <li>Bayar total sewa <strong>Rp {{ number_format($transaksi->total_sewa, 0, ',', '.') }}</strong> secara tunai</li>
                                <li>Admin akan mengkonfirmasi dan menyerahkan barang</li>
                            </ol>
                            <p class="text-[10px] text-amber-700 mt-2 font-semibold">
                                ⚠ Pemesanan otomatis dibatalkan jika tidak dikonfirmasi admin hingga H+1 tanggal ambil pukul 23:59.
                            </p>
                        </div>
                    </div>
                </div>
            @else
                {{-- Midtrans: info pembayaran --}}
                <div class="px-8 py-5 bg-blue-50 border-b border-blue-200">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-xl text-blue-600 flex-shrink-0">credit_card</span>
                        <div class="text-xs text-blue-800 space-y-1">
                            <p class="font-bold uppercase tracking-wider">Instruksi Pembayaran Cashless</p>
                            <p>Selesaikan pembayaran melalui link Midtrans yang dikirim ke email Anda.</p>
                            <p class="font-semibold">Batas waktu pembayaran: 24 jam dari sekarang.</p>
                            <p class="text-[10px] text-blue-600">Stok barang akan dikonfirmasi otomatis setelah pembayaran berhasil.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Footer struk --}}
            <div class="px-8 py-4 bg-[#faf9f5] text-center">
                <p class="text-[9px] text-[#7b776c] uppercase tracking-widest">
                    Majelis Rental &bull; Terima kasih telah mempercayai kami
                </p>
            </div>
        </div>

        {{-- ── ACTION BUTTONS ── --}}
        <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <button onclick="window.print()"
                class="flex-1 flex items-center justify-center gap-2 py-3 border-2 border-[#4d462e] text-[#4d462e] text-[10px] uppercase tracking-widest font-bold rounded hover:bg-[#4d462e] hover:text-white transition-all">
                <span class="material-symbols-outlined text-base">print</span>
                Cetak / Simpan PDF
            </button>
            <a href="{{ route('home') }}"
                class="flex-1 flex items-center justify-center gap-2 py-3 bg-[#4d462e] text-[#F2E8C6] text-[10px] uppercase tracking-widest font-bold rounded hover:bg-[#655e44] transition-colors">
                <span class="material-symbols-outlined text-base">home</span>
                Kembali ke Beranda
            </a>
        </div>
    </div>

    {{-- Generate QR Code dari nomor transaksi --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new QRCode(document.getElementById('qrcode'), {
                text    : '{{ $transaksi->nomor_transaksi }}',
                width   : 80,
                height  : 80,
                colorDark  : '#251D1D',
                colorLight : '#ffffff',
                correctLevel: QRCode.CorrectLevel.H,
            });
        });
    </script>

    {{-- Print style --}}
    <style>
        @media print {
            body { background: white !important; }
            nav, footer, .print\:hidden, button, a[href] { display: none !important; }
            #struk { border: none !important; box-shadow: none !important; }
            .max-w-2xl { max-width: 100% !important; }
        }
    </style>

</body>
</html>
