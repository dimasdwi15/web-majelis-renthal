<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\JaminanIdentitas;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Services\OcrIdentitasService;
use Carbon\Carbon;
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
        private readonly OcrIdentitasService $ocrService
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function refreshCartSession(): array
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return $cart;
        }

        $barangList = Barang::with('fotoUtama')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        foreach ($cart as $id => $item) {
            $barang = $barangList->get($id);

            if (!$barang || $barang->status !== 'aktif') {
                unset($cart[$id]);
                continue;
            }

            $cart[$id]['nama']  = $barang->nama;
            $cart[$id]['harga'] = (float) $barang->harga_per_hari;
            $cart[$id]['stok']  = $barang->stok;
            $cart[$id]['foto']  = $barang->fotoUtama?->path_foto;

            if ($cart[$id]['qty'] > $barang->stok) {
                $cart[$id]['qty'] = max(1, $barang->stok);
            }
        }

        session(['cart' => $cart]);

        return $cart;
    }

    /**
     * Jalankan OCR dan kembalikan array hasil.
     * Return null jika file tidak valid (sebelum OCR dijalankan).
     */
    private function jalankanOcr(Request $request): ?array
    {
        if (!$request->hasFile('foto_identitas') || !$request->file('foto_identitas')->isValid()) {
            return null;
        }

        return $this->ocrService->validasi(
            $request->file('foto_identitas'),
            $request->input('jenis_identitas', '')
        );
    }

    private function errorResponse(bool $isAjax, string $message, int $status = 422)
    {
        if ($isAjax) {
            return response()->json(['message' => $message], $status);
        }
        return back()->with('error', $message)->withInput();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PUBLIC METHODS
    // ─────────────────────────────────────────────────────────────────────────

    public function index()
    {
        $cart = $this->refreshCartSession();

        if (empty($cart)) {
            return redirect()->route('katalog')
                ->with('error', 'Keranjang Anda kosong. Silakan pilih barang terlebih dahulu.');
        }

        return view('user.pages.checkout');
    }

    public function proses(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        // ── 1. Validasi Input Dasar ───────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'tanggal_ambil'     => ['required', 'date', 'after_or_equal:today'],
            'tanggal_kembali'   => ['required', 'date', 'after:tanggal_ambil'],
            'metode_pembayaran' => ['required', 'in:midtrans,tunai'],
            'jenis_identitas'   => ['required', 'in:KTP,SIM,PELAJAR'],
            'foto_identitas'    => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'tanggal_ambil.after_or_equal'  => 'Tanggal ambil minimal hari ini.',
            'tanggal_kembali.after'         => 'Tanggal kembali harus setelah tanggal ambil.',
            'foto_identitas.required'       => 'Foto identitas wajib diupload.',
            'foto_identitas.image'          => 'File harus berupa gambar (JPG/PNG/WEBP).',
            'foto_identitas.mimes'          => 'Format file harus JPG, PNG, atau WEBP.',
            'foto_identitas.max'            => 'Ukuran foto maksimal 5MB.',
        ]);

        if ($validator->fails()) {
            if ($isAjax) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // ── 2. Baca konten file ke memori SEBELUM OCR dijalankan ─────────────
        // PENTING (fix Windows/Laragon): PHP di Windows menginvalidasi path
        // internal UploadedFile setelah file diakses oleh GD / ob_start,
        // sehingga ->store() yang dipanggil SESUDAH OCR gagal dengan
        // "Path must not be empty". Solusi: baca konten + ekstensi ke memori
        // sekarang, lalu simpan via Storage::put() setelah OCR selesai.
        $fotoFile     = $request->file('foto_identitas');
        $fotoKonten   = file_get_contents($fotoFile->getRealPath() ?: $fotoFile->getPathname());
        $fotoEkstensi = $fotoFile->getClientOriginalExtension() ?: 'jpg';

        if ($fotoKonten === false || $fotoKonten === '') {
            return $this->errorResponse($isAjax, 'File foto identitas tidak dapat dibaca.');
        }

        // ── 3. Jalankan OCR — file asli boleh invalid setelah ini ────────────
        $hasilOcr = $this->jalankanOcr($request);

        if ($hasilOcr === null) {
            return $this->errorResponse($isAjax, 'File foto identitas tidak valid.');
        }

        if (!$hasilOcr['requiresManual'] && !$hasilOcr['valid']) {
            Log::info('[OCR] Validasi gagal', [
                'user_id'       => Auth::id(),
                'jenis'         => $request->jenis_identitas,
                'confidence'    => $hasilOcr['confidence'],
                'matchedGroups' => $hasilOcr['matchedGroups'],
            ]);
            return $this->errorResponse($isAjax, $hasilOcr['message']);
        }

        if ($hasilOcr['requiresManual']) {
            Log::warning('[OCR] Tidak tersedia, lanjutkan checkout tanpa verifikasi OCR.', [
                'user_id' => Auth::id(),
                'jenis'   => $request->jenis_identitas,
            ]);
        }

        // ── 4. Refresh Cart & Validasi Stok ──────────────────────────────────
        $cart = $this->refreshCartSession();

        if (empty($cart)) {
            return $this->errorResponse($isAjax, 'Keranjang kosong.');
        }

        $tglAmbil   = Carbon::parse($request->tanggal_ambil);
        $tglKembali = Carbon::parse($request->tanggal_kembali);
        $durasi     = $tglAmbil->diffInDays($tglKembali);

        $totalSewa  = 0;
        $itemsValid = [];

        foreach ($cart as $barangId => $item) {
            $barang = Barang::find($barangId);

            if (!$barang || $barang->status !== 'aktif') {
                return $this->errorResponse($isAjax, "Barang \"{$item['nama']}\" tidak tersedia.");
            }

            if ($request->metode_pembayaran === 'midtrans' && $barang->stok < $item['qty']) {
                return $this->errorResponse($isAjax, "Stok \"{$barang->nama}\" tidak mencukupi (tersisa {$barang->stok}).");
            }

            $subtotal   = $barang->harga_per_hari * $item['qty'] * $durasi;
            $totalSewa += $subtotal;

            $itemsValid[$barangId] = [
                'barang'         => $barang,
                'qty'            => $item['qty'],
                'harga_per_hari' => $barang->harga_per_hari,
                'subtotal'       => $subtotal,
            ];
        }

        // ── 5. Simpan ke Database (DB Transaction) ────────────────────────────
        DB::beginTransaction();

        $pathFoto = null; // track untuk cleanup jika rollback

        try {
            $nomorTransaksi = 'TRX-' . strtoupper(Str::random(8)) . '-' . now()->format('ymd');

            $transaksi = Transaksi::create([
                'user_id'           => Auth::id(),
                'nomor_transaksi'   => $nomorTransaksi,
                'status'            => 'menunggu_pembayaran',
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_pembayaran' => 'menunggu',
                'total_sewa'        => $totalSewa,
                'total_denda'       => 0,
                'total_charge'      => 0,
                'tanggal_ambil'     => $tglAmbil->toDateString(),
                'tanggal_kembali'   => $tglKembali->toDateString(),
            ]);

            foreach ($itemsValid as $barangId => $detail) {
                TransaksiDetail::create([
                    'transaksi_id'   => $transaksi->id,
                    'barang_id'      => $barangId,
                    'jumlah'         => $detail['qty'],
                    'harga_per_hari' => $detail['harga_per_hari'],
                    'durasi_hari'    => $durasi,
                    'subtotal'       => $detail['subtotal'],
                ]);

                if ($request->metode_pembayaran === 'midtrans') {
                    $detail['barang']->decrement('stok', $detail['qty']);
                }
            }

            // ── Simpan Foto Jaminan ───────────────────────────────────────────
            // Gunakan Storage::put() dengan konten yang sudah dibaca ke memori,
            // bukan ->store() pada UploadedFile yang sudah invalid setelah OCR.
            $namaFile = Str::random(40) . '.' . $fotoEkstensi;
            $pathFoto = 'jaminan/' . $namaFile;
            Storage::disk('public')->put($pathFoto, $fotoKonten);

            // Tentukan status OCR
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

            // ── Buat Record Pembayaran ─────────────────────────────────────────
            $pembayaran = Pembayaran::create([
                'transaksi_id' => $transaksi->id,
                'jenis'        => 'utama',
                'jumlah'       => $totalSewa,
                'metode'       => $request->metode_pembayaran,
                'status'       => 'menunggu',
            ]);

            // ── Midtrans Snap Token ───────────────────────────────────────────
            $snapToken = null;

            if ($request->metode_pembayaran === 'midtrans') {
                Config::$serverKey    = config('midtrans.server_key');
                Config::$isProduction = config('midtrans.is_production');
                Config::$isSanitized  = true;
                Config::$is3ds        = true;

                $itemDetails = [];
                foreach ($itemsValid as $barangId => $detail) {
                    $itemDetails[] = [
                        'id'       => (string) $barangId,
                        'price'    => (int) round($detail['harga_per_hari'] * $durasi),
                        'quantity' => $detail['qty'],
                        'name'     => mb_substr($detail['barang']->nama, 0, 50),
                    ];
                }

                $params = [
                    'transaction_details' => [
                        'order_id'     => $nomorTransaksi,
                        'gross_amount' => (int) $totalSewa,
                    ],
                    'customer_details' => [
                        'first_name' => Auth::user()->name,
                        'email'      => Auth::user()->email,
                        'phone'      => Auth::user()->phone ?? '',
                    ],
                    'item_details' => $itemDetails,
                    'expiry' => [
                        'start_time' => now()->format('Y-m-d H:i:s O'),
                        'unit'       => 'hours',
                        'duration'   => 24,
                    ],
                ];

                $snapToken = Snap::getSnapToken($params);
                $pembayaran->update(['referensi_midtrans' => $nomorTransaksi]);
            }

            // ── Notifikasi Admin ──────────────────────────────────────────────
            $metodeLabel = $request->metode_pembayaran === 'midtrans'
                ? 'Cashless (Midtrans)'
                : 'Tunai (COD)';

            app(\App\Services\NotifikasiService::class)->notifTransaksiBaru(
                nomorTransaksi: $nomorTransaksi,
                namaUser: Auth::user()->name,
                transaksiId: $transaksi->id,
                metode: $metodeLabel
            );

            DB::commit();

            session()->forget('cart');

            // ── Response ──────────────────────────────────────────────────────
            if ($request->metode_pembayaran === 'midtrans') {
                return response()->json([
                    'snap_token'      => $snapToken,
                    'redirect_url'    => route('checkout.sukses', $transaksi->nomor_transaksi),
                    'nomor_transaksi' => $transaksi->nomor_transaksi,
                ]);
            }

            return redirect()->route('checkout.sukses', $transaksi->nomor_transaksi)
                ->with('metode', 'tunai');

        } catch (\Exception $e) {
            DB::rollBack();

            // Hapus file yang sudah terlanjur diupload jika transaksi gagal
            if ($pathFoto && Storage::disk('public')->exists($pathFoto)) {
                Storage::disk('public')->delete($pathFoto);
            }

            Log::error('[Checkout] Gagal proses transaksi', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $msg = 'Terjadi kesalahan sistem. Silakan coba lagi.';

            if (app()->isLocal()) {
                $msg .= ' (' . $e->getMessage() . ')';
            }

            return $this->errorResponse($isAjax, $msg, 500);
        }
    }

    public function sukses($nomorTransaksi)
    {
        $transaksi = Transaksi::with(['details.barang', 'jaminanIdentitas'])
            ->where('nomor_transaksi', $nomorTransaksi)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('user.pages.checkout-sukses', compact('transaksi'));
    }
}
