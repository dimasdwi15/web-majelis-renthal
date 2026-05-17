<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusTransaksi;
use App\Http\Controllers\Controller;
use App\Models\Denda;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Services\NotifikasiService;
use App\Services\TransaksiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ═══════════════════════════════════════════════════════════════════
 * MidtransCallbackController  —  VERSI FINAL (fix denda)
 * ═══════════════════════════════════════════════════════════════════
 *
 * ROOT CAUSE yang diperbaiki:
 * ───────────────────────────
 * TransaksiService::kirimTagihan() membuat record Pembayaran dengan:
 *   jenis = 'charge'   ← BUKAN 'denda'
 *
 * Controller lama:  where('jenis', 'denda')  → TIDAK KETEMU → warning
 * Controller baru:  whereIn('jenis', ['denda','charge']) → KETEMU ✓
 *
 * Cara verifikasi file ini sudah aktif:
 *   Lihat log Laravel — harus muncul "[CALLBACK-v2]" bukan "[API]"
 * ═══════════════════════════════════════════════════════════════════
 */
class MidtransCallbackController extends Controller
{
    // Tag versi — pastikan ini muncul di log setelah file di-replace
    private const LOG_TAG = '[CALLBACK-v2]';

    public function handle(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (empty($payload)) {
            $payload = $request->all();
        }

        $orderId    = $payload['order_id']           ?? null;
        $statusCode = $payload['status_code']        ?? null;
        $grossAmt   = $payload['gross_amount']       ?? null;
        $sigKey     = $payload['signature_key']      ?? null;
        $status     = $payload['transaction_status'] ?? null;
        $fraud      = $payload['fraud_status']       ?? 'accept';

        Log::info(self::LOG_TAG . ' MASUK', [
            'order_id' => $orderId,
            'status'   => $status,
            'amount'   => $grossAmt,
        ]);

        // ── Validasi payload ─────────────────────────────────────────────
        if (!$orderId || !$statusCode || !$grossAmt || !$sigKey) {
            Log::warning(self::LOG_TAG . ' payload tidak lengkap', $payload);
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // ── Validasi signature ───────────────────────────────────────────
        $serverKey         = config('midtrans.server_key');
        $grossFormatted    = number_format((float) $grossAmt, 2, '.', '');
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossFormatted . $serverKey);

        if ($expectedSignature !== $sigKey) {
            Log::warning(self::LOG_TAG . ' signature tidak valid', ['order_id' => $orderId]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // ── Routing ──────────────────────────────────────────────────────
        if (str_starts_with($orderId, 'CHARGE-') || str_starts_with($orderId, 'DENDA-')) {
            return $this->handleDendaPayment($orderId, $status, $fraud);
        }

        return $this->handleUtamaPayment($orderId, $status, $fraud);
    }

    // ════════════════════════════════════════════════════════════════════
    // PEMBAYARAN UTAMA (sewa)
    // ════════════════════════════════════════════════════════════════════
    private function handleUtamaPayment(string $orderId, string $status, string $fraud): JsonResponse
    {
        $transaksi = Transaksi::where('nomor_transaksi', $orderId)->first();

        // Coba strip suffix -R{timestamp} dari reopen payment
        if (!$transaksi && preg_match('/^(TRX-[A-Z0-9]+-\d+)-R\d+$/', $orderId, $m)) {
            $transaksi = Transaksi::where('nomor_transaksi', $m[1])->first();
            Log::info(self::LOG_TAG . ' utama: lookup nomor asli', ['nomor_asli' => $m[1]]);
        }

        if (!$transaksi) {
            Log::warning(self::LOG_TAG . ' utama: transaksi tidak ditemukan', ['order_id' => $orderId]);
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $pembayaran = $transaksi->pembayaranUtama;

        if (!$pembayaran) {
            Log::warning(self::LOG_TAG . ' utama: pembayaran tidak ditemukan', ['order_id' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if ($pembayaran->status === 'lunas') {
            Log::info(self::LOG_TAG . ' utama: idempotency — sudah lunas', ['order_id' => $orderId]);
            return response()->json(['message' => 'Already processed']);
        }

        if (in_array($status, ['capture', 'settlement']) && ($fraud === 'accept' || $status === 'settlement')) {
            DB::transaction(function () use ($pembayaran, $transaksi) {
                $pembayaran->update(['status' => 'lunas', 'dibayar_pada' => now()]);
                $transaksi->update(['status' => StatusTransaksi::Berjalan, 'status_pembayaran' => 'lunas']);
            });

            Log::info(self::LOG_TAG . ' utama: BERHASIL → BERJALAN', [
                'order_id'     => $orderId,
                'transaksi_id' => $transaksi->id,
            ]);

            app(NotifikasiService::class)->notifStatusUpdate(
                userId:         $transaksi->user_id,
                transaksiId:    $transaksi->id,
                nomorTransaksi: $transaksi->nomor_transaksi,
                statusBaru:     'berjalan',
                pesan:          'Pembayaran berhasil! Barang siap diambil.',
            );

        } elseif ($status === 'pending') {
            $pembayaran->update(['status' => 'menunggu']);
            Log::info(self::LOG_TAG . ' utama: pending', ['order_id' => $orderId]);

        } elseif (in_array($status, ['deny', 'expire', 'cancel'])) {
            DB::transaction(function () use ($pembayaran, $transaksi) {
                $pembayaran->update(['status' => 'gagal']);
                foreach ($transaksi->details as $d) {
                    $d->barang->increment('stok', $d->jumlah);
                }
                $transaksi->update(['status' => StatusTransaksi::Dibatalkan, 'status_pembayaran' => 'gagal']);
            });
            Log::info(self::LOG_TAG . ' utama: gagal/expired', ['order_id' => $orderId, 'status' => $status]);
        }

        return response()->json(['message' => 'OK']);
    }

    // ════════════════════════════════════════════════════════════════════
    // PEMBAYARAN DENDA
    //
    // Format A: CHARGE-{transaksi_id}-{timestamp}
    //   → record Pembayaran dengan jenis = 'charge'  (dibuat kirimTagihan())
    //
    // Format B: DENDA-{denda_id}-{timestamp}  (format lama)
    //   → record Pembayaran dengan jenis = 'denda'
    //
    // FIX: whereIn('jenis', ['denda','charge']) agar Format A ketemu ✓
    // ════════════════════════════════════════════════════════════════════
    private function handleDendaPayment(string $orderId, string $status, string $fraud): JsonResponse
    {
        $parts = explode('-', $orderId);

        if (str_starts_with($orderId, 'CHARGE-')) {
            // ── Format A: CHARGE-{transaksi_id}-{timestamp} ──────────────
            $transaksiId = $parts[1] ?? null;

            Log::info(self::LOG_TAG . ' denda CHARGE: lookup transaksi', [
                'order_id'     => $orderId,
                'transaksi_id' => $transaksiId,
            ]);

            if (!$transaksiId) {
                Log::error(self::LOG_TAG . ' denda CHARGE: format order_id tidak valid', ['order_id' => $orderId]);
                return response()->json(['message' => 'Invalid order format'], 400);
            }

            $transaksi = Transaksi::find($transaksiId);

            if (!$transaksi) {
                Log::error(self::LOG_TAG . ' denda CHARGE: transaksi tidak ditemukan di DB', [
                    'order_id'     => $orderId,
                    'transaksi_id' => $transaksiId,
                ]);
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            // ── FIX UTAMA: whereIn(['denda','charge']) ────────────────────
            // kirimTagihan() membuat record dengan jenis='charge'
            // Versi lama hanya cari jenis='denda' → selalu gagal
            $allPembayaran = Pembayaran::where('transaksi_id', $transaksiId)
                ->whereIn('jenis', ['denda', 'charge'])
                ->get();

            Log::info(self::LOG_TAG . ' denda CHARGE: semua record pembayaran denda/charge', [
                'transaksi_id' => $transaksiId,
                'count'        => $allPembayaran->count(),
                'records'      => $allPembayaran->map(fn($p) => [
                    'id'     => $p->id,
                    'jenis'  => $p->jenis,
                    'status' => $p->status,
                    'jumlah' => $p->jumlah,
                ])->toArray(),
            ]);

            // Ambil yang masih menunggu
            $pembayaran = $allPembayaran->where('status', 'menunggu')->sortByDesc('id')->first();

            // Fallback: ambil yang terbaru walau bukan menunggu
            if (!$pembayaran) {
                $pembayaran = $allPembayaran->sortByDesc('id')->first();
                Log::warning(self::LOG_TAG . ' denda CHARGE: fallback — tidak ada status menunggu, ambil terbaru', [
                    'transaksi_id' => $transaksiId,
                    'pembayaran'   => $pembayaran ? ['id' => $pembayaran->id, 'status' => $pembayaran->status] : null,
                ]);
            }

            if (!$pembayaran) {
                // ── TIDAK ADA RECORD SAMA SEKALI: buat otomatis ──────────
                // Ini terjadi jika kirimTagihan() gagal simpan pembayaran,
                // atau transaksi baru dibuat manual tanpa lewat service
                Log::warning(self::LOG_TAG . ' denda CHARGE: TIDAK ADA record pembayaran — membuat otomatis', [
                    'transaksi_id' => $transaksiId,
                    'order_id'     => $orderId,
                ]);

                $pembayaran = Pembayaran::create([
                    'transaksi_id' => $transaksiId,
                    'jenis'        => 'charge',
                    'jumlah'       => $transaksi->total_denda,
                    'metode'       => 'midtrans',
                    'status'       => 'menunggu',
                ]);
            }

            // Idempotency
            if ($pembayaran->status === 'lunas') {
                Log::info(self::LOG_TAG . ' denda CHARGE: idempotency — sudah lunas', ['order_id' => $orderId]);
                return response()->json(['message' => 'Already processed']);
            }

        } else {
            // ── Format B: DENDA-{denda_id}-{timestamp} ───────────────────
            $dendaId = $parts[1] ?? null;

            Log::info(self::LOG_TAG . ' denda DENDA: lookup denda', [
                'order_id' => $orderId,
                'denda_id' => $dendaId,
            ]);

            if (!$dendaId) {
                Log::error(self::LOG_TAG . ' denda DENDA: format tidak valid', ['order_id' => $orderId]);
                return response()->json(['message' => 'Invalid order format'], 400);
            }

            $denda = Denda::with('transaksi')->find($dendaId);

            if (!$denda) {
                Log::error(self::LOG_TAG . ' denda DENDA: denda_id tidak ditemukan', [
                    'order_id' => $orderId,
                    'denda_id' => $dendaId,
                ]);
                return response()->json(['message' => 'Denda not found'], 404);
            }

            $transaksi = $denda->transaksi;

            $pembayaran = Pembayaran::where('transaksi_id', $denda->transaksi_id)
                ->whereIn('jenis', ['denda', 'charge'])
                ->where('status', 'menunggu')
                ->latest()
                ->first();

            if (!$pembayaran) {
                $pembayaran = Pembayaran::where('transaksi_id', $denda->transaksi_id)
                    ->whereIn('jenis', ['denda', 'charge'])
                    ->latest()
                    ->first();
            }

            if (!$pembayaran) {
                Log::warning(self::LOG_TAG . ' denda DENDA: pembayaran tidak ditemukan, membuat otomatis', [
                    'order_id' => $orderId,
                ]);
                $pembayaran = Pembayaran::create([
                    'transaksi_id' => $denda->transaksi_id,
                    'jenis'        => 'denda',
                    'jumlah'       => $transaksi->total_denda,
                    'metode'       => 'midtrans',
                    'status'       => 'menunggu',
                ]);
            }

            if ($pembayaran->status === 'lunas') {
                Log::info(self::LOG_TAG . ' denda DENDA: idempotency — sudah lunas', ['order_id' => $orderId]);
                return response()->json(['message' => 'Already processed']);
            }
        }

        return $this->prosesPembayaranDenda($pembayaran, $transaksi, $orderId, $status);
    }

    // ════════════════════════════════════════════════════════════════════
    // Proses update DB setelah validasi
    // ════════════════════════════════════════════════════════════════════
    private function prosesPembayaranDenda(
        Pembayaran $pembayaran,
        Transaksi  $transaksi,
        string     $orderId,
        string     $status,
    ): JsonResponse {

        if (in_array($status, ['capture', 'settlement'])) {
            DB::transaction(function () use ($pembayaran, $transaksi) {
                // 1. Tandai record pembayaran lunas
                $pembayaran->update(['status' => 'lunas', 'dibayar_pada' => now()]);

                // 2. Tandai semua denda transaksi ini sebagai dibayar
                $transaksi->denda()->whereNull('dibayar_pada')->update(['dibayar_pada' => now()]);

                // 3. Update status transaksi → selesai
                $transaksi->update([
                    'status'            => StatusTransaksi::Selesai,
                    'status_pembayaran' => 'lunas',
                ]);
            });

            // Refresh dari DB untuk notifikasi
            $transaksi->refresh();

            Log::info(self::LOG_TAG . ' denda: BERHASIL → SELESAI', [
                'order_id'       => $orderId,
                'transaksi_id'   => $transaksi->id,
                'status_db'      => $transaksi->status,
            ]);

            app(NotifikasiService::class)->notifStatusUpdate(
                userId:         $transaksi->user_id,
                transaksiId:    $transaksi->id,
                nomorTransaksi: $transaksi->nomor_transaksi,
                statusBaru:     'selesai',
                pesan:          'Pembayaran denda berhasil. Transaksi selesai. Terima kasih!',
            );

        } elseif ($status === 'pending') {
            $pembayaran->update(['status' => 'menunggu']);
            Log::info(self::LOG_TAG . ' denda: pending', ['order_id' => $orderId]);

        } elseif (in_array($status, ['deny', 'expire', 'cancel'])) {
            $pembayaran->update(['status' => 'gagal']);
            Log::info(self::LOG_TAG . ' denda: gagal', ['order_id' => $orderId, 'status' => $status]);
        }

        return response()->json(['message' => 'OK']);
    }
}
