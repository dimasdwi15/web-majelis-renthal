<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusTransaksi;
use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\JaminanIdentitas;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Services\OcrIdentitasService;
use App\Services\RewardsService;
use App\Services\TransaksiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

class CheckoutController extends Controller
{
    public function __construct(
        protected TransaksiService    $transaksiService,
        protected OcrIdentitasService $ocrService,
        protected RewardsService      $rewardsService,
    ) {}

    // ── GET /api/checkout/history ─────────────────────────────────────────
    public function history(): JsonResponse
    {
        $transaksi = Transaksi::with([
            'details.barang.fotos',
            'denda',
            'pembayaran',
            'userVoucher.template',
            'userVoucher.mysteryBoxItem',
        ])
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(20);

        // Inject foto_utama & foto_utama_url ke setiap detail (sama seperti detailLengkap)
        $data = $transaksi->toArray();

        // Pre-load semua mystery_box_items indexed by title untuk lookup orphan vouchers
        // (voucher dari mystery box lama yang mystery_box_item_id-nya belum tersimpan)
        $mysteryItemsByTitle = \App\Models\MysteryBoxItem::all()
            ->keyBy('title');

        foreach ($data['data'] as &$trx) {
            // ── Inject foto_utama_url ke setiap detail ────────────────────────────
            foreach ($trx['details'] as &$detail) {
                $fotos    = $detail['barang']['fotos'] ?? [];
                $pathFoto = !empty($fotos) ? ($fotos[0]['path_foto'] ?? null) : null;

                if ($pathFoto) {
                    $detail['barang']['foto_utama']     = $pathFoto;
                    $detail['barang']['foto_utama_url'] = asset('storage/' . $pathFoto);
                } else {
                    $detail['barang']['foto_utama']     = null;
                    $detail['barang']['foto_utama_url'] = null;
                }
            }
            unset($detail);

            if (isset($trx['user_voucher']) && is_array($trx['user_voucher'])) {
                $uv     = &$trx['user_voucher'];
                $rarity = 'Common';

                if (!empty($uv['template']['rarity'])) {
                    // Voucher dari redeem XP → pakai rarity dari voucher_templates
                    $rarity = $uv['template']['rarity'];
                } elseif (!empty($uv['mystery_box_item']['rarity'])) {
                    // Voucher mystery box modern (FK sudah ada) → pakai rarity dari mystery_box_items
                    $rarity = $uv['mystery_box_item']['rarity'];
                } elseif (!empty($uv['mystery_title_snapshot'])) {
                    // Voucher orphan: cari mystery_box_item berdasarkan judul snapshot
                    $matchedItem = $mysteryItemsByTitle->get($uv['mystery_title_snapshot']);
                    if ($matchedItem) {
                        $rarity = $matchedItem->rarity;
                    }
                }

                $uv['computed_rarity'] = $rarity; // field baru untuk Flutter
                unset($uv);
            }
        }
        unset($trx);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // ── GET /api/checkout/{id}/detail-lengkap ─────────────────────────────
    //
    // FIX #1: eager load barang.fotos (relasi barang_foto) agar foto tampil
    // FIX #2: tambahkan durasi_hari di root response (diambil dari detail pertama)
    // FIX #3: foto denda — pastikan field 'url' selalu terisi dengan URL lengkap
    //
    public function detailLengkap(int $id): JsonResponse
    {
        $transaksi = Transaksi::with([
            'details.barang.fotos',
            'denda.foto',
            'pembayaran',
            'jaminanIdentitas',
            'userVoucher.template',
            'userVoucher.mysteryBoxItem',
        ])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $data = $transaksi->toArray();

        // ── FIX #2: tambah durasi_hari di root ───────────────────────────
        // Ambil dari detail pertama, fallback hitung dari tanggal
        $durasiHari = null;
        if (!empty($data['details'])) {
            $durasiHari = $data['details'][0]['durasi_hari'] ?? null;
        }
        if ($durasiHari === null) {
            $tglAmbil   = Carbon::parse($transaksi->tanggal_ambil);
            $tglKembali = Carbon::parse($transaksi->tanggal_kembali);
            $durasiHari = max(1, $tglAmbil->diffInDays($tglKembali));
        }
        $data['durasi_hari'] = $durasiHari;

        // ── FIX #1: tambahkan foto_utama_url pada setiap barang di detail ─
        // Tabel barang tidak punya kolom foto_utama — foto ada di barang_foto
        // Relasi: Barang hasMany BarangFoto (fotos)
        foreach ($data['details'] as &$detail) {
            $barangArr = $detail['barang'] ?? [];

            // Ambil foto pertama dari relasi fotos
            $fotos    = $barangArr['fotos'] ?? [];
            $pathFoto = null;

            if (!empty($fotos)) {
                $pathFoto = $fotos[0]['path_foto'] ?? null;
            }

            if ($pathFoto) {
                $detail['barang']['foto_utama']     = $pathFoto;
                $detail['barang']['foto_utama_url'] = asset('storage/' . $pathFoto);
            } else {
                $detail['barang']['foto_utama']     = null;
                $detail['barang']['foto_utama_url'] = null;
            }
        }
        unset($detail);

        // ── Inject foto_jaminan_url ───────────────────────────────────────
        if (!empty($data['jaminan_identitas'])) {
            $j = $data['jaminan_identitas'];
            $pathJaminan = $j['path_file'] ?? null;
            $data['jaminan_identitas']['foto_url'] = $pathJaminan
                ? asset('storage/' . $pathJaminan)
                : null;
        }

        // ── FIX #3: pastikan URL foto denda selalu terisi ────────────────
        // ── FIX #3: pastikan URL foto denda selalu terisi ────────────────
        foreach ($data['denda'] as &$d) {
            if (!empty($d['foto'])) {
                foreach ($d['foto'] as &$f) {
                    $pathFoto = $f['path_foto'] ?? null;
                    if ($pathFoto) {
                        $f['url']  = asset('storage/' . $pathFoto);
                        $f['path'] = $pathFoto;
                    }
                }
                unset($f);
            }
        }
        unset($d);

        if (isset($data['user_voucher']) && is_array($data['user_voucher'])) {
            $uv     = &$data['user_voucher'];
            $rarity = 'Common';

            if (!empty($uv['template']['rarity'])) {
                // Voucher dari redeem XP
                $rarity = $uv['template']['rarity'];
            } elseif (!empty($uv['mystery_box_item']['rarity'])) {
                // Voucher mystery box modern (FK ada)
                $rarity = $uv['mystery_box_item']['rarity'];
            } elseif (!empty($uv['mystery_title_snapshot'])) {
                // Voucher orphan: lookup ke mystery_box_items by title snapshot
                $matchedItem = \App\Models\MysteryBoxItem::where(
                    'title',
                    $uv['mystery_title_snapshot']
                )->first();

                if ($matchedItem) {
                    $rarity = $matchedItem->rarity;
                }
            }

            $uv['computed_rarity'] = $rarity;
            unset($uv);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // ── GET /api/checkout/{id} ────────────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $transaksi = Transaksi::with(['details.barang', 'denda', 'pembayaran', 'userVoucher.template'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $transaksi,
        ]);
    }

    // ── POST /api/checkout ────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        // ── 1. Validasi input ─────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'tanggal_ambil'     => 'required|date|after_or_equal:today',
            'tanggal_kembali'   => 'required|date|after:tanggal_ambil',
            'metode_pembayaran' => 'required|in:midtrans,tunai',
            'jenis_identitas'   => 'required|in:KTP,SIM,PELAJAR',
            'foto_identitas'    => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'items'             => 'required|array|min:1',
            'items.*.barang_id' => 'required|integer|exists:barang,id',
            'items.*.qty'       => 'required|integer|min:1',
            'voucher_code'      => 'nullable|string',  // unique_code dari user_vouchers
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // ── 2. Baca konten file foto sebelum OCR ──────────────────────────
        $fotoFile   = $request->file('foto_identitas');
        $fotoKonten = file_get_contents($fotoFile->getRealPath() ?: $fotoFile->getPathname());
        $fotoExt    = $fotoFile->getClientOriginalExtension() ?: 'jpg';

        if ($fotoKonten === false || $fotoKonten === '') {
            return response()->json([
                'success' => false,
                'message' => 'File foto identitas tidak dapat dibaca.',
            ], 422);
        }

        // ── 3. OCR validasi identitas ─────────────────────────────────────
        $hasilOcr = null;
        if ($fotoFile->isValid()) {
            $hasilOcr = $this->ocrService->validasi(
                $fotoFile,
                $request->input('jenis_identitas', '')
            );
        }

        if ($hasilOcr === null) {
            return response()->json([
                'success' => false,
                'message' => 'File foto identitas tidak valid.',
            ], 422);
        }

        if (!$hasilOcr['requiresManual'] && !$hasilOcr['valid']) {
            return response()->json([
                'success' => false,
                'message' => $hasilOcr['message'],
            ], 422);
        }

        // ── 4. Validasi stok & hitung total ──────────────────────────────
        $tglAmbil   = Carbon::parse($request->tanggal_ambil);
        $tglKembali = Carbon::parse($request->tanggal_kembali);
        $durasi     = max(1, $tglAmbil->diffInDays($tglKembali));

        $totalSewa  = 0.0;
        $itemsValid = [];

        foreach ($request->items as $item) {
            $barangId = (int) $item['barang_id'];
            $qty      = (int) $item['qty'];

            $barang = Barang::find($barangId);

            if (!$barang || $barang->status !== 'aktif') {
                return response()->json([
                    'success' => false,
                    'message' => "Barang ID {$barangId} tidak tersedia.",
                ], 422);
            }

            if ($request->metode_pembayaran === 'midtrans' && $barang->stok < $qty) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok \"{$barang->nama}\" tidak mencukupi (tersisa {$barang->stok}).",
                ], 422);
            }

            $subtotal   = $barang->harga_per_hari * $qty * $durasi;
            $totalSewa += $subtotal;

            $itemsValid[] = [
                'barang'         => $barang,
                'qty'            => $qty,
                'harga_per_hari' => $barang->harga_per_hari,
                'subtotal'       => $subtotal,
            ];
        }

        // ── 5. Validasi voucher (opsional) ────────────────────────────
        $appliedVoucher    = null;
        $voucherDiscount   = 0;
        $freeItemBarangId  = null;
        $voucherCode       = $request->input('voucher_code');

        if ($voucherCode) {
            $voucherResult = $this->rewardsService->applyVoucher(
                Auth::user(),
                $voucherCode,
                $totalSewa
            );

            if (! $voucherResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $voucherResult['message'],
                ], 422);
            }

            $appliedVoucher   = $voucherResult['voucher'];
            $voucherDiscount  = (int) $voucherResult['discount'];
            $freeItemBarangId = $voucherResult['free_barang_id'];

            // Kurangi total sewa dengan diskon voucher
            $totalSewa = max(0, $totalSewa - $voucherDiscount);
        }

        // ── 6. Simpan ke database ─────────────────────────────────────────
        DB::beginTransaction();
        $pathFoto = null;

        try {
            $nomorTransaksi = 'TRX-' . strtoupper(Str::random(8)) . '-' . now()->format('ymd');

            $transaksi = Transaksi::create([
                'user_id'           => Auth::id(),
                'nomor_transaksi'   => $nomorTransaksi,
                'status'            => StatusTransaksi::MenungguPembayaran,
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_pembayaran' => 'menunggu',
                'total_sewa'        => $totalSewa,
                'total_denda'       => 0,
                'total_charge'      => 0,
                'tanggal_ambil'     => $tglAmbil->toDateString(),
                'tanggal_kembali'   => $tglKembali->toDateString(),
                'batas_pembayaran'  => $request->metode_pembayaran === 'tunai'
                    ? $tglAmbil->copy()->addDay()
                    : now()->addHours(24),
            ]);

            foreach ($itemsValid as $detail) {
                TransaksiDetail::create([
                    'transaksi_id'   => $transaksi->id,
                    'barang_id'      => $detail['barang']->id,
                    'jumlah'         => $detail['qty'],
                    'harga_per_hari' => $detail['harga_per_hari'],
                    'durasi_hari'    => $durasi,
                    'subtotal'       => $detail['subtotal'],
                ]);

                if ($request->metode_pembayaran === 'midtrans') {
                    $detail['barang']->decrement('stok', $detail['qty']);
                }
            }

            // ── Tambah free item dari voucher (harga 0) ──────────────────
            if ($freeItemBarangId) {
                $barangGratis = Barang::find($freeItemBarangId);
                if ($barangGratis) {
                    TransaksiDetail::create([
                        'transaksi_id'   => $transaksi->id,
                        'barang_id'      => $barangGratis->id,
                        'jumlah'         => 1,
                        'harga_per_hari' => 0,
                        'durasi_hari'    => $durasi,
                        'subtotal'       => 0,
                    ]);
                }
            }

            // ── Simpan foto jaminan ───────────────────────────────────────
            $namaFile = Str::random(40) . '.' . $fotoExt;
            $pathFoto = 'jaminan/' . $namaFile;
            Storage::disk('public')->put($pathFoto, $fotoKonten);

            $statusOcr = match (true) {
                $hasilOcr['requiresManual']                        => 'belum_diverifikasi',
                $hasilOcr['valid'] && !$hasilOcr['requiresManual'] => 'terverifikasi_otomatis',
                default                                            => 'gagal_otomatis',
            };

            JaminanIdentitas::create([
                'transaksi_id'            => $transaksi->id,
                'user_id'                 => Auth::id(),
                'jenis_identitas'         => $request->jenis_identitas,
                'path_file'               => $pathFoto,
                'status'                  => 'aktif',
                'status_ocr'              => $statusOcr,
                'ocr_confidence'          => $hasilOcr['confidence'],
                'perlu_verifikasi_manual' => $hasilOcr['requiresManual'],
                'diverifikasi_pada'       => ($hasilOcr['valid'] && !$hasilOcr['requiresManual'])
                    ? now()
                    : null,
            ]);

            // ── Buat record pembayaran ────────────────────────────────────
            $pembayaran = Pembayaran::create([
                'transaksi_id' => $transaksi->id,
                'jenis'        => 'utama',
                'jumlah'       => $totalSewa,
                'metode'       => $request->metode_pembayaran,
                'status'       => 'menunggu',
            ]);

            // ── Midtrans Snap Token ───────────────────────────────────────
            $snapToken   = null;
            $redirectUrl = null;

            if ($request->metode_pembayaran === 'midtrans') {
                Config::$serverKey    = config('midtrans.server_key');
                Config::$isProduction = config('midtrans.is_production');
                Config::$isSanitized  = true;
                Config::$is3ds        = true;

                $itemDetails = [];
                foreach ($itemsValid as $detail) {
                    $itemDetails[] = [
                        'id'       => (string) $detail['barang']->id,
                        'price'    => (int) round($detail['harga_per_hari'] * $durasi),
                        'quantity' => $detail['qty'],
                        'name'     => mb_substr($detail['barang']->nama, 0, 50),
                    ];
                }

                if ($voucherDiscount > 0) {
                    $itemDetails[] = [
                        'id'       => 'VOUCHER-DISC',
                        'price'    => -(int) $voucherDiscount,
                        'quantity' => 1,
                        'name'     => 'Diskon Voucher',
                    ];
                }

                $params = [
                    'transaction_details' => [
                        // FIX #4: order_id = nomor_transaksi ASLI (tanpa suffix time)
                        // agar MidtransCallbackController bisa lookup via nomor_transaksi
                        'order_id'     => $nomorTransaksi,
                        'gross_amount' => (int) $totalSewa,
                    ],
                    'customer_details' => [
                        'first_name' => Auth::user()->name,
                        'email'      => Auth::user()->email,
                        'phone'      => Auth::user()->phone ?? '',
                    ],
                    'item_details' => $itemDetails,
                    'expiry'       => [
                        'start_time' => now()->format('Y-m-d H:i:s O'),
                        'unit'       => 'hours',
                        'duration'   => 24,
                    ],
                    'callbacks' => [
                        'finish' => config('app.url') . '/payment/finish',
                    ],
                ];

                $snapToken = Snap::getSnapToken($params);

                // Simpan snap token sebagai referensi_midtrans
                $pembayaran->update(['referensi_midtrans' => $snapToken]);

                $redirectUrl = config('midtrans.is_production')
                    ? 'https://app.midtrans.com/snap/v2/vtweb/' . $snapToken
                    : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . $snapToken;
            }

            // ── Notifikasi admin ──────────────────────────────────────────
            $metodeLabel = $request->metode_pembayaran === 'midtrans'
                ? 'Cashless (Midtrans)'
                : 'Tunai (COD)';

            app(\App\Services\NotifikasiService::class)->notifTransaksiBaru(
                nomorTransaksi: $nomorTransaksi,
                namaUser: Auth::user()->name,
                transaksiId: $transaksi->id,
                metode: $metodeLabel,
            );

            // ── Mark voucher digunakan ────────────────────────────────────
            if ($appliedVoucher) {
                $this->rewardsService->useVoucher($appliedVoucher, $transaksi->id);
            }

            // ── Award XP: hanya untuk Midtrans (pembayaran langsung konfirmasi) ──────────
            // COD: XP diberikan nanti di TransaksiService::bayarCod()
            //      yaitu setelah admin ubah status → 'berjalan'
            if ($request->metode_pembayaran === 'midtrans') {
                $transaksi->refresh();
                $this->rewardsService->awardCheckoutXp($transaksi);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Checkout berhasil.',
                'data'    => [
                    'transaksi_id'    => $transaksi->id,
                    'nomor_transaksi' => $nomorTransaksi,
                    'status'          => $transaksi->status,
                    'total_sewa'      => $totalSewa,
                    'durasi_hari'     => $durasi,
                    'snap_token'      => $snapToken,
                    'redirect_url'    => $redirectUrl,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($pathFoto && Storage::disk('public')->exists($pathFoto)) {
                Storage::disk('public')->delete($pathFoto);
            }

            Log::error('[API Checkout] Gagal', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $msg = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            if (app()->isLocal()) {
                $msg .= ' (' . $e->getMessage() . ')';
            }

            return response()->json([
                'success' => false,
                'message' => $msg,
            ], 500);
        }
    }

    // ── POST /api/checkout/validasi-identitas ─────────────────────────────
    public function validasiIdentitas(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'jenis_identitas' => 'required|in:KTP,SIM,PELAJAR',
            'foto_identitas'  => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $fotoFile = $request->file('foto_identitas');

        if (!$fotoFile || !$fotoFile->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'File foto identitas tidak valid.',
            ], 422);
        }

        $hasilOcr = $this->ocrService->validasi(
            $fotoFile,
            $request->input('jenis_identitas', '')
        );

        return response()->json([
            'success' => true,
            'data'    => $hasilOcr,
        ]);
    }

    // ── POST /api/checkout/{id}/reopen-payment ────────────────────────────
    //
    // FIX #4: reopen payment harus menggunakan nomor_transaksi ASLI sebagai
    // order_id agar callback Midtrans bisa menemukan transaksi via
    // WHERE nomor_transaksi = order_id
    //
    public function reopenPayment(int $id): JsonResponse
    {
        $transaksi = Transaksi::with('pembayaranUtama')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        if ($transaksi->status !== StatusTransaksi::MenungguPembayaran) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini sudah tidak bisa dibayar ulang (status: '
                    . $transaksi->status->value . ').',
            ], 422);
        }

        if ($transaksi->status_pembayaran === 'lunas') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini sudah lunas.',
            ], 422);
        }

        Config::$serverKey    = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized  = true;
        Config::$is3ds        = true;

        $pembayaran = $transaksi->pembayaranUtama;
        $snapToken  = $pembayaran?->referensi_midtrans;

        // Snap token Midtrans berbentuk UUID, bukan diawali 'TRX-'
        $isValidSnapToken = $snapToken && !str_starts_with($snapToken, 'TRX-');

        if (!$isValidSnapToken) {
            // Generate snap token baru — gunakan nomor_transaksi ASLI sebagai order_id
            try {
                $snapToken = $this->_generateSnapToken($transaksi);
            } catch (\Exception $e) {
                Log::error('reopenPayment: gagal generate snap token', [
                    'transaksi_id' => $id,
                    'error'        => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuka halaman pembayaran: ' . $e->getMessage(),
                ], 500);
            }

            if ($pembayaran) {
                $pembayaran->update(['referensi_midtrans' => $snapToken]);
            } else {
                Pembayaran::create([
                    'transaksi_id'       => $transaksi->id,
                    'jenis'              => 'utama',
                    'jumlah'             => $transaksi->total_sewa,
                    'metode'             => 'midtrans',
                    'status'             => 'menunggu',
                    'referensi_midtrans' => $snapToken,
                ]);
            }
        }

        $redirectUrl = config('midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v2/vtweb/' . $snapToken
            : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . $snapToken;

        return response()->json([
            'success'      => true,
            'snap_token'   => $snapToken,
            'redirect_url' => $redirectUrl,
            'message'      => 'Silakan selesaikan pembayaran.',
        ]);
    }

    // ── POST /api/checkout/{id}/bayar-denda ──────────────────────────────
    public function bayarDenda(int $id): JsonResponse
    {
        $transaksi = Transaksi::where('user_id', Auth::id())->findOrFail($id);

        if ((float) $transaksi->total_denda <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada denda yang perlu dibayar.',
            ], 422);
        }

        $sudahLunas = $transaksi->pembayaran()
            ->where('jenis', 'denda')
            ->where('status', 'lunas')
            ->exists();

        if ($sudahLunas) {
            return response()->json([
                'success' => false,
                'message' => 'Denda sudah dilunasi.',
            ], 422);
        }

        try {
            $snapToken = $this->transaksiService->kirimTagihan($transaksi);

            $redirectUrl = config('midtrans.is_production')
                ? 'https://app.midtrans.com/snap/v2/vtweb/' . $snapToken
                : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . $snapToken;

            return response()->json([
                'success'      => true,
                'snap_token'   => $snapToken,
                'redirect_url' => $redirectUrl,
                'message'      => 'Silakan selesaikan pembayaran denda.',
            ]);
        } catch (\Exception $e) {
            Log::error('bayarDenda error', [
                'transaksi_id' => $id,
                'error'        => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat tagihan denda: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── Private: generate Midtrans snap token ─────────────────────────────
    private function _generateSnapToken(Transaksi $transaksi): string
    {
        // Cek apakah order_id ini pernah dipakai di Midtrans sebelumnya.
        // Jika iya (misalnya transaksi expired di Midtrans lalu reopen),
        // tambahkan suffix agar tidak duplikat di sisi Midtrans.
        // Namun kita tetap menyimpan nomor_transaksi asli di DB agar
        // callback bisa ditemukan.
        //
        // Strategi: coba pakai nomor_transaksi asli dulu.
        // Jika Midtrans throw "duplicate order id", tambahkan suffix.
        try {
            $params = [
                'transaction_details' => [
                    'order_id'     => $transaksi->nomor_transaksi,
                    'gross_amount' => (int) $transaksi->total_sewa,
                ],
                'customer_details' => [
                    'first_name' => $transaksi->user->name,
                    'email'      => $transaksi->user->email,
                    'phone'      => $transaksi->user->phone ?? '',
                ],
                'callbacks' => [
                    'finish' => config('app.url') . '/payment/finish',
                ],
            ];

            return Snap::getSnapToken($params);
        } catch (\Exception $e) {
            // Jika duplikat order_id, gunakan suffix timestamp
            // Callback handler sudah disiapkan untuk strip suffix ini
            $orderId = $transaksi->nomor_transaksi . '-R' . time();

            // Simpan order_id yang dipakai agar callback bisa matching
            $transaksi->update(['nomor_transaksi_midtrans' => $orderId]);

            $params = [
                'transaction_details' => [
                    'order_id'     => $orderId,
                    'gross_amount' => (int) $transaksi->total_sewa,
                ],
                'customer_details' => [
                    'first_name' => $transaksi->user->name,
                    'email'      => $transaksi->user->email,
                    'phone'      => $transaksi->user->phone ?? '',
                ],
                'callbacks' => [
                    'finish' => config('app.url') . '/payment/finish',
                ],
            ];

            return Snap::getSnapToken($params);
        }
    }
}
