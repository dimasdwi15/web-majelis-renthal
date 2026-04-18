<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Struk Pesanan {{ $transaksi->nomor_transaksi }} | MAJELIS RENTAL</title>

    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">

    {{-- QR Code library --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f0ede6;
            -webkit-font-smoothing: antialiased;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            line-height: 1;
            vertical-align: middle;
        }
        @media print {
            body { background: white !important; }
            .no-print { display: none !important; }
            #struk { border: none !important; box-shadow: none !important; }
            .max-w-2xl { max-width: 100% !important; }
            .print-wrapper { padding: 0 !important; }
        }
    </style>
</head>

<body>

    {{-- Top Nav (no-print) --}}
    <div class="no-print sticky top-0 z-10 px-4 py-3 flex items-center gap-3" style="background: #1e1714; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <a href="{{ route('user.pesanan.show', $transaksi->id) }}"
           class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest transition-colors hover:opacity-70"
           style="color: #a8956a;">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            Kembali
        </a>
        <p class="text-[#F2E8C6] text-sm font-black flex-1 text-center">STRUK PESANAN</p>
        <div class="flex items-center gap-2">
            <button onclick="window.print()"
                    class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg transition-all hover:opacity-80"
                    style="background: #655e44; color: #F2E8C6;">
                <span class="material-symbols-outlined text-sm">print</span>
                Cetak
            </button>
        </div>
    </div>

    <div class="print-wrapper max-w-2xl mx-auto px-4 py-8">

        {{-- Status Header --}}
        <div class="text-center mb-6 no-print">
            @php
                $statusLabel = match($transaksi->status) {
                    'menunggu_pembayaran' => ['text' => 'Menunggu Pembayaran', 'icon' => 'pending',      'color' => '#d97706', 'bg' => '#fef3c7'],
                    'dibayar'             => ['text' => 'Sudah Dibayar',       'icon' => 'check_circle', 'color' => '#16a34a', 'bg' => '#dcfce7'],
                    'berjalan'            => ['text' => 'Sedang Berjalan',     'icon' => 'moving',       'color' => '#16a34a', 'bg' => '#dcfce7'],
                    'terlambat'           => ['text' => 'Terlambat',           'icon' => 'timer_off',    'color' => '#dc2626', 'bg' => '#fee2e2'],
                    'selesai'             => ['text' => 'Selesai',             'icon' => 'verified',     'color' => '#655e44', 'bg' => '#F2E8C6'],
                    default               => ['text' => ucfirst($transaksi->status->value), 'icon' => 'info', 'color' => '#7b776c', 'bg' => '#e7e4dc'],
                };
            @endphp
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3"
                 style="background: {{ $statusLabel['bg'] }};">
                <span class="material-symbols-outlined text-3xl" style="color: {{ $statusLabel['color'] }};">{{ $statusLabel['icon'] }}</span>
            </div>
            <h1 class="text-2xl font-black uppercase tracking-tighter" style="color: #2f342e;">{{ $statusLabel['text'] }}</h1>
            <p class="text-sm mt-1" style="color: #7b776c;">Struk resmi pesanan Anda</p>
        </div>

        {{-- ===== STRUK ===== --}}
        <div id="struk" class="rounded-xl overflow-hidden" style="background: #fff; border: 1px solid #ccc6b9; box-shadow: 0 4px 20px rgba(37,29,29,0.10);">

            {{-- Struk Header --}}
            <div class="px-8 py-6 text-[#F2E8C6]" style="background: #1e1714;">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[9px] uppercase tracking-[0.3em] mb-1" style="opacity: 0.5;">Majelis Rental</p>
                        <p class="font-black text-lg uppercase tracking-tight">Struk Pesanan</p>
                        <p class="text-[10px] mt-0.5" style="opacity: 0.5;">
                            {{ $transaksi->metode_pembayaran === 'tunai' ? 'Bayar tunai di toko saat pengambilan' : 'Pembayaran via Midtrans' }}
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <div id="qrcode" class="bg-white p-1 rounded"></div>
                    </div>
                </div>
            </div>

            {{-- Nomor Transaksi --}}
            <div class="px-8 py-4 text-center" style="background: #faf9f5; border-bottom: 1px solid #e7e4dc;">
                <p class="text-[9px] uppercase tracking-widest mb-1" style="color: #7b776c;">Nomor Transaksi</p>
                <p class="text-2xl font-black tracking-wider" style="color: #4d462e;">{{ $transaksi->nomor_transaksi }}</p>
            </div>

            {{-- Info Transaksi --}}
            <div class="px-8 py-5 grid grid-cols-2 gap-4 text-sm" style="border-bottom: 1px solid #e7e4dc;">
                <div>
                    <p class="text-[9px] uppercase tracking-widest mb-0.5" style="color: #7b776c;">Nama Penyewa</p>
                    <p class="font-semibold" style="color: #1b1c1a;">{{ Auth::user()->name }}</p>
                </div>
                <div>
                    <p class="text-[9px] uppercase tracking-widest mb-0.5" style="color: #7b776c;">Tanggal Pesan</p>
                    <p class="font-semibold" style="color: #1b1c1a;">{{ $transaksi->created_at->format('d M Y, H:i') }}</p>
                </div>
                <div>
                    <p class="text-[9px] uppercase tracking-widest mb-0.5" style="color: #7b776c;">Tanggal Ambil</p>
                    <p class="font-semibold" style="color: #1b1c1a;">{{ \Carbon\Carbon::parse($transaksi->tanggal_ambil)->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-[9px] uppercase tracking-widest mb-0.5" style="color: #7b776c;">Tanggal Kembali</p>
                    <p class="font-semibold" style="color: #1b1c1a;">{{ \Carbon\Carbon::parse($transaksi->tanggal_kembali)->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-[9px] uppercase tracking-widest mb-0.5" style="color: #7b776c;">Durasi</p>
                    <p class="font-semibold" style="color: #1b1c1a;">
                        {{ \Carbon\Carbon::parse($transaksi->tanggal_ambil)->diffInDays($transaksi->tanggal_kembali) }} Hari
                    </p>
                </div>
                <div>
                    <p class="text-[9px] uppercase tracking-widest mb-0.5" style="color: #7b776c;">Metode Bayar</p>
                    <p class="font-semibold" style="color: #1b1c1a;">
                        {{ $transaksi->metode_pembayaran === 'midtrans' ? 'Cashless (Midtrans)' : 'Tunai di Toko' }}
                    </p>
                </div>
            </div>

            {{-- Daftar Barang --}}
            <div class="px-8 py-5" style="border-bottom: 1px solid #e7e4dc;">
                <p class="text-[9px] uppercase tracking-widest mb-3" style="color: #7b776c;">Detail Barang Sewa</p>
                <div class="space-y-4">
                    @foreach($transaksi->details as $detail)
                        <div class="flex items-center gap-3">
                            {{-- Item image --}}
                            <div class="w-12 h-12 rounded-lg overflow-hidden flex-shrink-0" style="background: #f4f2ec;">
                                @if($detail->barang->fotoUtama)
                                    <img src="{{ Storage::url($detail->barang->fotoUtama->path_foto) }}"
                                         alt="{{ $detail->barang->nama }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <span class="material-symbols-outlined" style="color: #ccc6b9; font-size: 20px;">inventory_2</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold uppercase text-sm" style="color: #1b1c1a;">{{ $detail->barang->nama ?? '—' }}</p>
                                <p class="text-[10px]" style="color: #7b776c;">
                                    {{ $detail->jumlah }} unit
                                    × Rp {{ number_format($detail->harga_per_hari, 0, ',', '.') }}/hari
                                    × {{ $detail->durasi_hari }} hari
                                </p>
                            </div>
                            <span class="font-bold text-sm flex-shrink-0" style="color: #4d462e;">
                                Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Total --}}
            <div class="px-8 py-5" style="border-bottom: 1px solid #e7e4dc;">
                <div class="flex justify-between items-baseline">
                    <span class="text-[10px] font-bold uppercase tracking-widest" style="color: #7b776c;">Total Sewa</span>
                    <span class="text-3xl font-black" style="color: #4d462e;">
                        Rp {{ number_format($transaksi->total_sewa, 0, ',', '.') }}
                    </span>
                </div>
                @if($transaksi->total_denda > 0)
                    <div class="flex justify-between items-baseline mt-2 pt-2" style="border-top: 1px solid #f0ede6;">
                        <span class="text-[10px] font-bold uppercase tracking-widest" style="color: #dc2626;">Total Denda</span>
                        <span class="text-xl font-black" style="color: #dc2626;">
                            + Rp {{ number_format($transaksi->total_denda, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex justify-between items-baseline mt-2 pt-2" style="border-top: 2px solid #e7e4dc;">
                        <span class="text-[10px] font-black uppercase tracking-widest" style="color: #2f342e;">Grand Total</span>
                        <span class="text-2xl font-black" style="color: #2f342e;">
                            Rp {{ number_format($transaksi->total_sewa + $transaksi->total_denda, 0, ',', '.') }}
                        </span>
                    </div>
                @endif
                @if($transaksi->metode_pembayaran === 'tunai')
                    <p class="text-[10px] mt-1" style="color: #d97706;">
                        * Bayar tunai saat pengambilan barang di toko
                    </p>
                @endif
            </div>

            {{-- Status Instruksi --}}
            @if($transaksi->status === 'menunggu_pembayaran' && $transaksi->metode_pembayaran === 'tunai')
                <div class="px-8 py-5" style="background: #fffbeb; border-bottom: 1px solid #fde68a;">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-xl flex-shrink-0" style="color: #d97706;">storefront</span>
                        <div class="text-xs space-y-1.5" style="color: #78350f;">
                            <p class="font-bold uppercase tracking-wider">Instruksi Pengambilan (COD)</p>
                            <ol class="list-decimal list-inside space-y-1 leading-relaxed">
                                <li>Datang ke toko pada <strong>{{ \Carbon\Carbon::parse($transaksi->tanggal_ambil)->format('d M Y') }}</strong></li>
                                <li>Tunjukkan <strong>nomor transaksi</strong> atau QR Code kepada admin</li>
                                <li>Serahkan <strong>kartu identitas</strong> sebagai jaminan</li>
                                <li>Bayar total sewa <strong>Rp {{ number_format($transaksi->total_sewa, 0, ',', '.') }}</strong> secara tunai</li>
                                <li>Admin akan mengkonfirmasi dan menyerahkan barang</li>
                            </ol>
                        </div>
                    </div>
                </div>
            @elseif($transaksi->status === 'selesai')
                <div class="px-8 py-5" style="background: #f0fdf4; border-bottom: 1px solid #bbf7d0;">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-xl flex-shrink-0" style="color: #16a34a;">verified</span>
                        <div class="text-xs" style="color: #14532d;">
                            <p class="font-bold uppercase tracking-wider mb-1">Transaksi Selesai</p>
                            <p>Terima kasih telah menggunakan layanan Majelis Rental. Semoga pengalaman Anda menyenangkan!</p>
                        </div>
                    </div>
                </div>
            @elseif(in_array($transaksi->status, ['berjalan', 'dibayar']))
                <div class="px-8 py-5" style="background: #eff6ff; border-bottom: 1px solid #bfdbfe;">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-xl flex-shrink-0" style="color: #2563eb;">info</span>
                        <div class="text-xs" style="color: #1e40af;">
                            <p class="font-bold uppercase tracking-wider mb-1">Status Sewa Aktif</p>
                            <p>Harap kembalikan barang sebelum <strong>{{ \Carbon\Carbon::parse($transaksi->tanggal_kembali)->format('d M Y') }}</strong> untuk menghindari denda keterlambatan.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Footer Struk --}}
            <div class="px-8 py-4 text-center" style="background: #faf9f5;">
                <p class="text-[9px] uppercase tracking-widest" style="color: #7b776c;">
                    Majelis Rental &bull; Terima kasih telah mempercayai kami
                </p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-5 flex flex-col sm:flex-row gap-3 no-print">
            <button onclick="window.print()"
                    class="flex-1 flex items-center justify-center gap-2 py-3 rounded-lg transition-all hover:opacity-80 font-bold text-[10px] uppercase tracking-widest"
                    style="border: 2px solid #655e44; color: #655e44; background: transparent;">
                <span class="material-symbols-outlined text-base">print</span>
                Cetak / Simpan PDF
            </button>
            <a href="{{ route('user.pesanan.show', $transaksi->id) }}"
               class="flex-1 flex items-center justify-center gap-2 py-3 rounded-lg transition-all hover:opacity-90 text-[#F2E8C6] font-bold text-[10px] uppercase tracking-widest"
               style="background: #655e44;">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                Kembali ke Detail
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new QRCode(document.getElementById('qrcode'), {
                text:        '{{ $transaksi->nomor_transaksi }}',
                width:       80,
                height:      80,
                colorDark:   '#1e1714',
                colorLight:  '#ffffff',
                correctLevel: QRCode.CorrectLevel.H,
            });
        });
    </script>

</body>
</html>
