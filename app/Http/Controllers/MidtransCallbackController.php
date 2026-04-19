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
     */
    public function handle(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        
        Log::info('MIDTRANS CALLBACK MASUK', $request->all());

        $serverKey = config('midtrans.server_key');

        // ⚠️ FIX PENTING: format gross_amount harus sama persis
        $grossAmount = number_format((float) $request->gross_amount, 2, '.', '');

        $signature = hash(
            'sha512',
            $request->order_id .
                $request->status_code .
                $grossAmount .
                $serverKey
        );

        // ❌ jika gagal di sini → callback ditolak
        if ($signature !== $request->signature_key) {
            Log::warning('SIGNATURE TIDAK VALID', [
                'expected' => $signature,
                'actual'   => $request->signature_key,
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $orderId = $request->order_id;
        $status  = $request->transaction_status;
        $fraud   = $request->fraud_status ?? 'accept';

        // 🔥 DETEKSI TIPE ORDER
        if (str_starts_with($orderId, 'DENDA-') || str_starts_with($orderId, 'CHARGE-')) {
            return $this->handleDendaPayment($orderId, $status, $fraud);
        }

        // DEFAULT: transaksi utama
        return $this->handleUtamaPayment($orderId, $status, $fraud);

        $transaksi = \App\Models\Transaksi::where('nomor_transaksi', $orderId)->first();

        if (!$transaksi) {
            Log::warning('TRANSAKSI TIDAK DITEMUKAN', ['order_id' => $orderId]);
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $pembayaran = $transaksi->pembayaranUtama;

        if (!$pembayaran) {
            Log::warning('PEMBAYARAN TIDAK DITEMUKAN', ['order_id' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Hindari double update
        if ($pembayaran->status === 'lunas') {
            return response()->json(['message' => 'Already processed']);
        }

        // ✅ SUCCESS
        if (in_array($status, ['capture', 'settlement'])) {

            DB::transaction(function () use ($pembayaran, $transaksi) {

                $pembayaran->update([
                    'status'       => 'lunas',
                    'dibayar_pada' => now(),
                ]);

                $transaksi->update([
                    'status'            => \App\Enums\StatusTransaksi::Berjalan,
                    'status_pembayaran' => 'lunas',
                ]);
            });
        }
        // ⏳ PENDING
        elseif ($status === 'pending') {
            $pembayaran->update(['status' => 'menunggu']);
        }
        // ❌ GAGAL
        elseif (in_array($status, ['expire', 'cancel', 'deny'])) {

            DB::transaction(function () use ($pembayaran, $transaksi) {

                $pembayaran->update(['status' => 'gagal']);

                foreach ($transaksi->details as $detail) {
                    $detail->barang->increment('stok', $detail->jumlah);
                }

                $transaksi->update([
                    'status'            => \App\Enums\StatusTransaksi::Dibatalkan,
                    'status_pembayaran' => 'gagal',
                ]);
            });
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Handle pembayaran utama (sewa).
     *
     * Alur setelah pembayaran berhasil:
     *   menunggu_pembayaran → berjalan (langsung, tanpa perlu konfirmasi admin)
     *
     * Stok sudah dikurangi saat checkout, sehingga tidak perlu decrement lagi di sini.
     */
    private function handleUtamaPayment(string $orderId, string $status, string $fraud)
    {
        $transaksi = Transaksi::where('nomor_transaksi', $orderId)->first();

        if (!$transaksi) {
            Log::warning('Midtrans callback: transaksi tidak ditemukan', [
                'order_id' => $orderId,
            ]);
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $pembayaran = $transaksi->pembayaranUtama;

        if (!$pembayaran) {
            Log::warning('Midtrans callback: pembayaran tidak ditemukan', [
                'order_id' => $orderId,
            ]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // ⛔ Hindari double update (idempotency)
        if ($pembayaran->status === 'lunas') {
            return response()->json(['message' => 'Already processed']);
        }

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

                app(NotifikasiService::class)->notifStatusUpdate(
                    userId: $transaksi->user_id,
                    transaksiId: $transaksi->id,
                    nomorTransaksi: $transaksi->nomor_transaksi,
                    statusBaru: 'berjalan',
                    pesan: 'Pembayaran berhasil! Barang siap diambil.'
                );
            }
        } elseif ($status === 'pending') {
            $pembayaran->update(['status' => 'menunggu']);
        } elseif (in_array($status, ['deny', 'expire', 'cancel'])) {

            DB::transaction(function () use ($pembayaran, $transaksi) {

                $pembayaran->update(['status' => 'gagal']);

                foreach ($transaksi->details as $detail) {
                    $detail->barang->increment('stok', $detail->jumlah);
                }

                $transaksi->update([
                    'status'            => StatusTransaksi::Dibatalkan,
                    'status_pembayaran' => 'gagal',
                ]);
            });
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Handle pembayaran denda (charge).
     */
    private function handleDendaPayment(string $orderId, string $status, string $fraud)
    {
        // format: DENDA-{denda_id}-{timestamp}
        $parts = explode('-', $orderId);
        $dendaId = $parts[1] ?? null;

        if (!$dendaId) {
            return response()->json(['message' => 'Invalid order format'], 400);
        }

        $denda = \App\Models\Denda::with('transaksi')->find($dendaId);

        if (!$denda) {
            Log::warning('DENDA TIDAK DITEMUKAN', ['order_id' => $orderId]);
            return response()->json(['message' => 'Denda not found'], 404);
        }

        $pembayaran = \App\Models\Pembayaran::where('jenis', 'denda')
            ->where('transaksi_id', $denda->transaksi_id)
            ->where('status', 'menunggu')
            ->latest()
            ->first();

        if (!$pembayaran) {
            Log::warning('PEMBAYARAN DENDA TIDAK DITEMUKAN', ['order_id' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $transaksi = $denda->transaksi;

        // ✅ SUCCESS
        if (in_array($status, ['capture', 'settlement'])) {

            DB::transaction(function () use ($pembayaran, $denda, $transaksi) {

                // update pembayaran
                $pembayaran->update([
                    'status'       => 'lunas',
                    'dibayar_pada' => now(),
                ]);

                // 🔥 update denda (INI YANG ANDA LUPAKAN)
                $denda->update([
                    'dibayar_pada' => now()
                ]);

                // 🔥 selesai transaksi
                app(\App\Services\TransaksiService::class)
                    ->selesaikanTransaksi($transaksi);
            });
        }

        // ⏳ pending
        elseif ($status === 'pending') {
            $pembayaran->update(['status' => 'menunggu']);
        }

        // ❌ gagal
        elseif (in_array($status, ['deny', 'expire', 'cancel'])) {
            $pembayaran->update(['status' => 'gagal']);
        }

        return response()->json(['message' => 'OK']);
    }
}
