<?php

namespace App\Http\Controllers;

use App\Enums\StatusTransaksi;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Services\NotifikasiService;
use App\Services\TransaksiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MidtransCallbackController extends Controller
{
    /**
     * Handle Midtrans notification webhook.
     * Route: POST /midtrans/callback (exclude CSRF)
     *
     * PENTING: Pastikan URL notifikasi di dashboard Midtrans diisi:
     *   https://<your-domain>/midtrans/callback
     */
    public function handle(Request $request)
    {
        // ── Parse payload (Midtrans mengirim JSON body) ─────────────────
        $payload = json_decode($request->getContent(), true);

        if (empty($payload)) {
            // Fallback: gunakan request biasa jika bukan JSON murni
            $payload = $request->all();
        }

        Log::info('MIDTRANS CALLBACK MASUK', $payload);

        $serverKey   = config('midtrans.server_key');
        $orderId     = $payload['order_id']          ?? null;
        $statusCode  = $payload['status_code']       ?? null;
        $grossAmount = $payload['gross_amount']      ?? null;
        $signatureKey = $payload['signature_key']    ?? null;
        $status      = $payload['transaction_status'] ?? null;
        $fraud       = $payload['fraud_status']      ?? 'accept';

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            Log::warning('MIDTRANS CALLBACK: payload tidak lengkap', $payload);
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // ── Validasi signature ──────────────────────────────────────────
        // Format gross_amount harus sama persis (2 desimal, titik, tanpa koma)
        $grossAmountFormatted = number_format((float) $grossAmount, 2, '.', '');

        $expectedSignature = hash(
            'sha512',
            $orderId . $statusCode . $grossAmountFormatted . $serverKey
        );

        if ($expectedSignature !== $signatureKey) {
            Log::warning('MIDTRANS CALLBACK: signature tidak valid', [
                'expected' => $expectedSignature,
                'actual'   => $signatureKey,
                'order_id' => $orderId,
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // ── Routing berdasarkan tipe order ──────────────────────────────
        if (str_starts_with($orderId, 'DENDA-') || str_starts_with($orderId, 'CHARGE-')) {
            return $this->handleDendaPayment($orderId, $status, $fraud);
        }

        return $this->handleUtamaPayment($orderId, $status, $fraud);
    }

    /**
     * Handle pembayaran utama (sewa).
     *
     * Alur setelah pembayaran berhasil:
     *   menunggu_pembayaran → berjalan (langsung, tanpa perlu konfirmasi admin)
     *
     * Stok sudah dikurangi saat checkout, sehingga tidak perlu decrement lagi di sini.
     */
    private function handleUtamaPayment(string $orderId, string $status, string $fraud): \Illuminate\Http\JsonResponse
    {
        $transaksi = Transaksi::where('nomor_transaksi', $orderId)->first();

        if (!$transaksi) {
            Log::warning('Midtrans callback: transaksi tidak ditemukan', ['order_id' => $orderId]);
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $pembayaran = $transaksi->pembayaranUtama;

        if (!$pembayaran) {
            Log::warning('Midtrans callback: pembayaran tidak ditemukan', ['order_id' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // ⛔ Hindari double update (idempotency)
        if ($pembayaran->status === 'lunas') {
            Log::info('Midtrans callback: sudah diproses (idempotency)', ['order_id' => $orderId]);
            return response()->json(['message' => 'Already processed']);
        }

        // ✅ SUCCESS
        if (in_array($status, ['capture', 'settlement'])) {
            if ($fraud === 'accept' || $status === 'settlement') {

                DB::transaction(function () use ($pembayaran, $transaksi) {
                    $pembayaran->update([
                        'status'       => 'lunas',
                        'dibayar_pada' => now(),
                    ]);

                    $transaksi->update([
                        'status'            => StatusTransaksi::Berjalan,
                        'status_pembayaran' => 'lunas',
                    ]);
                });

                Log::info('Midtrans: pembayaran utama berhasil', ['order_id' => $orderId]);

                app(NotifikasiService::class)->notifStatusUpdate(
                    userId: $transaksi->user_id,
                    transaksiId: $transaksi->id,
                    nomorTransaksi: $transaksi->nomor_transaksi,
                    statusBaru: 'berjalan',
                    pesan: 'Pembayaran berhasil! Barang siap diambil.'
                );
            }
        }
        // ⏳ PENDING
        elseif ($status === 'pending') {
            $pembayaran->update(['status' => 'menunggu']);
            Log::info('Midtrans: pembayaran pending', ['order_id' => $orderId]);
        }
        // ❌ GAGAL
        elseif (in_array($status, ['deny', 'expire', 'cancel'])) {

            DB::transaction(function () use ($pembayaran, $transaksi) {
                $pembayaran->update(['status' => 'gagal']);

                // Kembalikan stok yang sudah dikurangi saat checkout
                foreach ($transaksi->details as $detail) {
                    $detail->barang->increment('stok', $detail->jumlah);
                }

                $transaksi->update([
                    'status'            => StatusTransaksi::Dibatalkan,
                    'status_pembayaran' => 'gagal',
                ]);
            });

            Log::info('Midtrans: pembayaran gagal/expired/cancel', [
                'order_id' => $orderId,
                'status'   => $status,
            ]);
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Handle pembayaran denda / charge.
     * Format order_id: DENDA-{denda_id}-{timestamp}
     */
    private function handleDendaPayment(string $orderId, string $status, string $fraud): \Illuminate\Http\JsonResponse
    {
        // Ambil denda_id dari format: DENDA-{id}-{timestamp}
        $parts   = explode('-', $orderId);
        $dendaId = $parts[1] ?? null;

        if (!$dendaId) {
            Log::warning('Midtrans denda: format order_id tidak valid', ['order_id' => $orderId]);
            return response()->json(['message' => 'Invalid order format'], 400);
        }

        $denda = \App\Models\Denda::with('transaksi')->find($dendaId);

        if (!$denda) {
            Log::warning('Midtrans denda: denda tidak ditemukan', ['order_id' => $orderId]);
            return response()->json(['message' => 'Denda not found'], 404);
        }

        $pembayaran = \App\Models\Pembayaran::where('jenis', 'denda')
            ->where('transaksi_id', $denda->transaksi_id)
            ->where('status', 'menunggu')
            ->latest()
            ->first();

        if (!$pembayaran) {
            Log::warning('Midtrans denda: pembayaran denda tidak ditemukan', ['order_id' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $transaksi = $denda->transaksi;

        // ✅ SUCCESS
        if (in_array($status, ['capture', 'settlement'])) {

            DB::transaction(function () use ($pembayaran, $denda, $transaksi) {
                $pembayaran->update([
                    'status'       => 'lunas',
                    'dibayar_pada' => now(),
                ]);

                $denda->update([
                    'dibayar_pada' => now(),
                ]);

                app(\App\Services\TransaksiService::class)
                    ->selesaikanTransaksi($transaksi);
            });

            Log::info('Midtrans: pembayaran denda berhasil', ['order_id' => $orderId]);
        }
        // ⏳ PENDING
        elseif ($status === 'pending') {
            $pembayaran->update(['status' => 'menunggu']);
        }
        // ❌ GAGAL
        elseif (in_array($status, ['deny', 'expire', 'cancel'])) {
            $pembayaran->update(['status' => 'gagal']);
            Log::info('Midtrans: pembayaran denda gagal', ['order_id' => $orderId, 'status' => $status]);
        }

        return response()->json(['message' => 'OK']);
    }
}
