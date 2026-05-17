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

        if (empty($payload)) {
            $payload = $request->all();
        }

        Log::info('MIDTRANS CALLBACK MASUK', $payload);

        $serverKey    = config('midtrans.server_key');
        $orderId      = $payload['order_id']           ?? null;
        $statusCode   = $payload['status_code']        ?? null;
        $grossAmount  = $payload['gross_amount']       ?? null;
        $signatureKey = $payload['signature_key']      ?? null;
        $status       = $payload['transaction_status'] ?? null;
        $fraud        = $payload['fraud_status']       ?? 'accept';

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            Log::warning('MIDTRANS CALLBACK: payload tidak lengkap', $payload);
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // ── Validasi signature ──────────────────────────────────────────
        $grossAmountFormatted = number_format((float) $grossAmount, 2, '.', '');
        $expectedSignature    = hash('sha512', $orderId . $statusCode . $grossAmountFormatted . $serverKey);

        if ($expectedSignature !== $signatureKey) {
            Log::warning('MIDTRANS CALLBACK: signature tidak valid', [
                'expected' => $expectedSignature,
                'actual'   => $signatureKey,
                'order_id' => $orderId,
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // ── Routing berdasarkan prefix order_id ────────────────────────
        //
        // CHARGE-{transaksi_id}-{timestamp}  → denda cashless dari Flutter/web
        //   dibuat oleh TransaksiService::kirimTagihan()
        //   record Pembayaran-nya ber-jenis 'charge'
        //
        // DENDA-{denda_id}-{timestamp}        → format lama (backward compat)
        //   record Pembayaran-nya ber-jenis 'denda'
        //
        // selain itu                          → pembayaran utama sewa
        //
        if (str_starts_with($orderId, 'CHARGE-')) {
            return $this->handleChargePayment($orderId, $status, $fraud);
        }

        if (str_starts_with($orderId, 'DENDA-')) {
            return $this->handleDendaPayment($orderId, $status, $fraud);
        }

        return $this->handleUtamaPayment($orderId, $status, $fraud);
    }

    // ────────────────────────────────────────────────────────────────────
    // Pembayaran utama (sewa)
    //
    // FIX: bayarUlang() di PesananController mengirim order_id dengan
    // suffix -R{timestamp} agar unik di Midtrans (misal: TRX-XXXX-260517-R1778994745).
    // Kode lama langsung WHERE nomor_transaksi = order_id sehingga tidak ketemu.
    // Solusi: strip suffix -R{digits} sebelum lookup ke database.
    // ────────────────────────────────────────────────────────────────────
    private function handleUtamaPayment(string $orderId, string $status, string $fraud): \Illuminate\Http\JsonResponse
    {
        // Kenali format bayar-ulang: TRX-XXXXXXXX-YYMMDD-R{timestamp}
        // dan kembalikan ke nomor transaksi asli untuk lookup.
        $nomorLookup = $orderId;
        if (preg_match('/^(TRX-[A-Z0-9]+-\d+)-R\d+$/', $orderId, $matches)) {
            $nomorLookup = $matches[1];
            Log::info('Midtrans callback: mendeteksi format bayar-ulang', [
                'order_id_asli' => $orderId,
                'nomor_lookup'  => $nomorLookup,
            ]);
        }

        $transaksi = Transaksi::where('nomor_transaksi', $nomorLookup)->first();

        if (!$transaksi) {
            Log::warning('Midtrans callback: transaksi tidak ditemukan', [
                'order_id'     => $orderId,
                'nomor_lookup' => $nomorLookup,
            ]);
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $pembayaran = $transaksi->pembayaranUtama;

        if (!$pembayaran) {
            Log::warning('Midtrans callback: pembayaran tidak ditemukan', [
                'order_id'     => $orderId,
                'transaksi_id' => $transaksi->id,
            ]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Idempotency
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
                    userId:         $transaksi->user_id,
                    transaksiId:    $transaksi->id,
                    nomorTransaksi: $transaksi->nomor_transaksi,
                    statusBaru:     'berjalan',
                    pesan:          'Pembayaran berhasil! Barang siap diambil.'
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

    // ────────────────────────────────────────────────────────────────────
    // Pembayaran denda format BARU: CHARGE-{transaksi_id}-{timestamp}
    //
    // FIX: Di versi lama, CHARGE-* masuk ke handleDendaPayment() yang
    // memparse parts[1] sebagai denda_id — padahal itu transaksi_id.
    // Denda::find(transaksi_id) tidak ketemu → warning "denda tidak ditemukan".
    //
    // Solusi: pisahkan handler CHARGE ke method sendiri, cari record
    // Pembayaran by transaksi_id dengan jenis IN ('charge','denda').
    // ────────────────────────────────────────────────────────────────────
    private function handleChargePayment(string $orderId, string $status, string $fraud): \Illuminate\Http\JsonResponse
    {
        $parts       = explode('-', $orderId);
        $transaksiId = $parts[1] ?? null;

        if (!$transaksiId) {
            Log::warning('Midtrans CHARGE: format order_id tidak valid', ['order_id' => $orderId]);
            return response()->json(['message' => 'Invalid order format'], 400);
        }

        $transaksi = Transaksi::find($transaksiId);

        if (!$transaksi) {
            Log::warning('Midtrans CHARGE: transaksi tidak ditemukan', [
                'order_id'     => $orderId,
                'transaksi_id' => $transaksiId,
            ]);
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // Cari record pembayaran jenis 'charge' atau 'denda' yang masih menunggu
        $pembayaran = Pembayaran::where('transaksi_id', $transaksiId)
            ->whereIn('jenis', ['charge', 'denda'])
            ->where('status', 'menunggu')
            ->latest()
            ->first();

        // Fallback: ambil yang terbaru walau statusnya bukan menunggu
        if (!$pembayaran) {
            $pembayaran = Pembayaran::where('transaksi_id', $transaksiId)
                ->whereIn('jenis', ['charge', 'denda'])
                ->latest()
                ->first();
        }

        // Jika benar-benar tidak ada, buat otomatis
        // (bisa terjadi jika kirimTagihan() gagal simpan atau race condition)
        if (!$pembayaran) {
            Log::warning('Midtrans CHARGE: tidak ada record pembayaran, membuat otomatis', [
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
            Log::info('Midtrans CHARGE: sudah diproses (idempotency)', ['order_id' => $orderId]);
            return response()->json(['message' => 'Already processed']);
        }

        // ✅ SUCCESS
        if (in_array($status, ['capture', 'settlement'])) {
            DB::transaction(function () use ($pembayaran, $transaksi) {
                // 1. Tandai pembayaran lunas
                $pembayaran->update([
                    'status'       => 'lunas',
                    'dibayar_pada' => now(),
                ]);

                // 2. Tandai semua denda yang belum dibayar sebagai lunas
                $transaksi->denda()->whereNull('dibayar_pada')->update([
                    'dibayar_pada' => now(),
                ]);

                // 3. Selesaikan transaksi
                $transaksi->update([
                    'status'            => StatusTransaksi::Selesai,
                    'status_pembayaran' => 'lunas',
                ]);
            });

            Log::info('Midtrans CHARGE: pembayaran denda berhasil → SELESAI', [
                'order_id'     => $orderId,
                'transaksi_id' => $transaksi->id,
            ]);

            app(NotifikasiService::class)->notifStatusUpdate(
                userId:         $transaksi->user_id,
                transaksiId:    $transaksi->id,
                nomorTransaksi: $transaksi->nomor_transaksi,
                statusBaru:     'selesai',
                pesan:          'Pembayaran denda berhasil. Transaksi selesai. Terima kasih!'
            );
        }
        // ⏳ PENDING
        elseif ($status === 'pending') {
            $pembayaran->update(['status' => 'menunggu']);
            Log::info('Midtrans CHARGE: pending', ['order_id' => $orderId]);
        }
        // ❌ GAGAL
        elseif (in_array($status, ['deny', 'expire', 'cancel'])) {
            $pembayaran->update(['status' => 'gagal']);
            Log::info('Midtrans CHARGE: gagal', ['order_id' => $orderId, 'status' => $status]);
        }

        return response()->json(['message' => 'OK']);
    }

    // ────────────────────────────────────────────────────────────────────
    // Pembayaran denda format LAMA: DENDA-{denda_id}-{timestamp}
    // TIDAK DIUBAH — persis seperti versi asli agar transaksi web lama aman
    // ────────────────────────────────────────────────────────────────────
    private function handleDendaPayment(string $orderId, string $status, string $fraud): \Illuminate\Http\JsonResponse
    {
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
