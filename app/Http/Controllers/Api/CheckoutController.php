<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\JaminanIdentitas;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Services\OcrIdentitasServiceMobile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly OcrIdentitasServiceMobile $ocrService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/checkout/validasi-identitas
    //
    // Validasi foto identitas SEBELUM checkout (preview / real-time).
    // Request: multipart/form-data
    //   - foto_identitas : file (jpg/png/jpeg, max 5MB)
    //   - jenis_identitas: KTP | SIM | PELAJAR
    // ─────────────────────────────────────────────────────────────────────────
    public function validasiIdentitas(Request $request): JsonResponse
    {
        $request->validate([
            'foto_identitas'  => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'jenis_identitas' => ['required', 'in:KTP,SIM,PELAJAR'],
        ]);

        $hasil = $this->ocrService->validasi(
            $request->file('foto_identitas'),
            $request->input('jenis_identitas'),
        );

        return response()->json([
            'success'          => true,
            'valid'            => $hasil['valid'],
            'confidence'       => $hasil['confidence'],
            'jenis_terdeteksi' => $hasil['jenis_terdeteksi'],
            'sesuai_jenis'     => $hasil['sesuai_jenis'],
            'pesan'            => $hasil['pesan'],
            'perlu_manual'     => $hasil['perlu_manual'],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/checkout
    //
    // Submit checkout lengkap.
    // Request: multipart/form-data
    //   - tanggal_ambil    : date (Y-m-d), >= hari ini
    //   - tanggal_kembali  : date (Y-m-d), > tanggal_ambil
    //   - metode_pembayaran: midtrans | tunai
    //   - jenis_identitas  : KTP | SIM | PELAJAR
    //   - foto_identitas   : file (jpg/png/jpeg, max 5MB)
    //   - items[][barang_id]: int
    //   - items[][qty]     : int (min 1)
    // ─────────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tanggal_ambil'     => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'tanggal_kembali'   => ['required', 'date', 'date_format:Y-m-d', 'after:tanggal_ambil'],
            'metode_pembayaran' => ['required', 'in:midtrans,tunai'],
            'jenis_identitas'   => ['required', 'in:KTP,SIM,PELAJAR'],
            'foto_identitas'    => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.barang_id' => ['required', 'integer', 'exists:barang,id'],
            'items.*.qty'       => ['required', 'integer', 'min:1'],
        ]);

        // ── Validasi identitas via AI (Groq LLaMA-4 Scout) ───────────────
        $hasilOcr = $this->ocrService->validasi(
            $request->file('foto_identitas'),
            $request->input('jenis_identitas'),
        );

        // Tolak jika AI yakin TIDAK valid dan tidak butuh verifikasi manual
        if (!$hasilOcr['valid'] && !$hasilOcr['perlu_manual']) {
            return response()->json([
                'success' => false,
                'message' => 'Foto identitas tidak valid: ' . $hasilOcr['pesan'],
                'ocr'     => [
                    'valid'            => false,
                    'confidence'       => $hasilOcr['confidence'],
                    'jenis_terdeteksi' => $hasilOcr['jenis_terdeteksi'],
                    'sesuai_jenis'     => $hasilOcr['sesuai_jenis'],
                    'pesan'            => $hasilOcr['pesan'],
                    'perlu_manual'     => $hasilOcr['perlu_manual'],
                ],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user           = Auth::user();
            $tanggalAmbil   = $request->input('tanggal_ambil');
            $tanggalKembali = $request->input('tanggal_kembali');
            $durasi         = (int) now()->parse($tanggalAmbil)
                                ->diffInDays(now()->parse($tanggalKembali));
            $durasi         = max($durasi, 1);

            // ── Hitung total & cek stok ───────────────────────────────────
            $totalSewa = 0;
            $itemsData = [];

            foreach ($request->input('items') as $item) {
                $barang = Barang::findOrFail($item['barang_id']);

                if ($barang->status !== 'aktif') {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Barang \"{$barang->nama}\" tidak tersedia.",
                    ], 422);
                }

                if ($barang->stok < $item['qty']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stok \"{$barang->nama}\" tidak mencukupi (tersedia: {$barang->stok}).",
                    ], 422);
                }

                $subtotal   = $barang->harga_per_hari * $item['qty'] * $durasi;
                $totalSewa += $subtotal;

                $itemsData[] = [
                    'barang'   => $barang,
                    'qty'      => $item['qty'],
                    'subtotal' => $subtotal,
                ];
            }

            // ── Buat transaksi ────────────────────────────────────────────
            $nomorTrx = $this->generateNomorTransaksi();

            $transaksi = Transaksi::create([
                'user_id'           => $user->id,
                'nomor_transaksi'   => $nomorTrx,
                'status'            => 'menunggu_pembayaran',
                'metode_pembayaran' => $request->input('metode_pembayaran'),
                'status_pembayaran' => 'menunggu',
                'total_sewa'        => $totalSewa,
                'total_denda'       => 0,
                'total_charge'      => 0,
                'tanggal_ambil'     => $tanggalAmbil,
                'tanggal_kembali'   => $tanggalKembali,
                'batas_pembayaran'  => $request->input('metode_pembayaran') === 'tunai'
                    ? now()->addHours(24)
                    : null,
            ]);

            // ── Simpan detail item + kurangi stok ─────────────────────────
            foreach ($itemsData as $item) {
                TransaksiDetail::create([
                    'transaksi_id'   => $transaksi->id,
                    'barang_id'      => $item['barang']->id,
                    'jumlah'         => $item['qty'],
                    'harga_per_hari' => $item['barang']->harga_per_hari,
                    'durasi_hari'    => $durasi,
                    'subtotal'       => $item['subtotal'],
                ]);

                $item['barang']->decrement('stok', $item['qty']);
            }

            // ── Upload & simpan jaminan identitas ─────────────────────────
            $foto     = $request->file('foto_identitas');
            $ext      = $foto->getClientOriginalExtension();
            $filename = 'jaminan/' . Str::random(40) . '.' . $ext;
            Storage::disk('public')->put(
                $filename,
                file_get_contents($foto->getRealPath())
            );

            // Tentukan status OCR berdasarkan hasil AI
            $statusOcr = match (true) {
                $hasilOcr['valid'] && !$hasilOcr['perlu_manual'] => 'terverifikasi_otomatis',
                $hasilOcr['perlu_manual']                         => 'belum_diverifikasi',
                default                                           => 'gagal_otomatis',
            };

            JaminanIdentitas::create([
                'transaksi_id'            => $transaksi->id,
                'user_id'                 => $user->id,
                'jenis_identitas'         => strtoupper($request->input('jenis_identitas')),
                'path_file'               => $filename,
                'status'                  => 'aktif',
                'status_ocr'              => $statusOcr,
                'ocr_confidence'          => $hasilOcr['confidence'],
                'perlu_verifikasi_manual' => $hasilOcr['perlu_manual'],
                'catatan_admin'           => null,
                'diverifikasi_pada'       => $hasilOcr['valid'] ? now() : null,
            ]);

            DB::commit();

            // ── Response ──────────────────────────────────────────────────
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat.',
                'data'    => [
                    'transaksi_id'      => $transaksi->id,
                    'nomor_transaksi'   => $transaksi->nomor_transaksi,
                    'status'            => $transaksi->status,
                    'metode_pembayaran' => $transaksi->metode_pembayaran,
                    'total_sewa'        => $transaksi->total_sewa,
                    'tanggal_ambil'     => $transaksi->tanggal_ambil,
                    'tanggal_kembali'   => $transaksi->tanggal_kembali,
                    'durasi_hari'       => $durasi,
                    'batas_pembayaran'  => $transaksi->batas_pembayaran,
                    'ocr'               => [
                        'status'           => $statusOcr,
                        'confidence'       => $hasilOcr['confidence'],
                        'jenis_terdeteksi' => $hasilOcr['jenis_terdeteksi'],
                        'sesuai_jenis'     => $hasilOcr['sesuai_jenis'],
                        'pesan'            => $hasilOcr['pesan'],
                        'perlu_manual'     => $hasilOcr['perlu_manual'],
                    ],
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CheckoutController@store error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server. Silakan coba lagi.',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/checkout/history
    // ─────────────────────────────────────────────────────────────────────────
    public function history(Request $request): JsonResponse
    {
        $transaksi = Transaksi::with(['details.barang', 'jaminanIdentitas'])
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $transaksi,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/checkout/{id}
    // ─────────────────────────────────────────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $transaksi = Transaksi::with(['details.barang', 'jaminanIdentitas'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $transaksi,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────────
    private function generateNomorTransaksi(): string
    {
        do {
            $kode = 'TRX-' . strtoupper(Str::random(8)) . '-' . now()->format('ymd');
        } while (Transaksi::where('nomor_transaksi', $kode)->exists());

        return $kode;
    }
}
