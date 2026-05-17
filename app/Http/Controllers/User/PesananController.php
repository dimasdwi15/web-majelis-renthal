<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\Denda;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;

class PesananController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaksi::with(['details.barang', 'denda'])
            ->where('user_id', Auth::id());

        if ($request->filled('status') && $request->status !== 'semua') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('nomor_transaksi', 'like', '%' . $request->search . '%');
        }

        $query->orderBy('created_at', $request->sort === 'terlama' ? 'asc' : 'desc');

        $pesanan = $query->paginate(10)->withQueryString();

        return view('user.pesanan.index', compact('pesanan'));
    }

    public function show(Transaksi $transaksi)
    {
        abort_if($transaksi->user_id !== Auth::id(), 403);

        $transaksi->load([
            'details.barang.fotoUtama',
            'pembayaran',
            'denda.foto',
            'jaminanIdentitas',
        ]);

        return view('user.pesanan.show', compact('transaksi'));
    }

    public function bayarDenda(Denda $denda)
    {
        $denda->load(['transaksi', 'foto']);

        abort_if($denda->transaksi->user_id !== Auth::id(), 403);
        abort_if($denda->dibayar_pada !== null, 403, 'Denda sudah dibayar.');

        $pembayaran = Pembayaran::where('transaksi_id', $denda->transaksi_id)
            ->where('jenis', 'denda')
            ->where('status', 'menunggu')
            ->first();

        $snapToken = $pembayaran?->referensi_midtrans;

        return view('user.pesanan.bayar-denda', compact('denda', 'snapToken'));
    }

    public function prosesBayarDenda(Request $request, Denda $denda)
    {
        $denda->load('transaksi');

        abort_if($denda->transaksi->user_id !== Auth::id(), 403);
        abort_if($denda->dibayar_pada !== null, 403, 'Denda sudah dibayar.');

        if ($request->metode === 'midtrans') {
            Config::$serverKey    = config('midtrans.server_key');
            Config::$isProduction = config('midtrans.is_production');
            Config::$isSanitized  = true;
            Config::$is3ds        = true;

            $params = [
                'transaction_details' => [
                    'order_id'     => 'DENDA-' . $denda->id . '-' . time(),
                    'gross_amount' => (int) $denda->jumlah,
                ],
                'customer_details' => [
                    'first_name' => Auth::user()->name,
                    'email'      => Auth::user()->email,
                    'phone'      => Auth::user()->phone ?? '',
                ],
            ];

            $snapToken = Snap::getSnapToken($params);

            Pembayaran::updateOrCreate(
                [
                    'transaksi_id' => $denda->transaksi_id,
                    'jenis'        => 'denda',
                    'status'       => 'menunggu',
                ],
                [
                    'jumlah'             => $denda->jumlah,
                    'metode'             => 'midtrans',
                    'referensi_midtrans' => $snapToken,
                ]
            );

            return redirect()->route('user.pesanan.bayar-denda', $denda->id);
        }

        return redirect()
            ->route('user.pesanan.show', $denda->transaksi_id)
            ->with('success', 'Silakan bayar denda secara tunai di toko kami.');
    }

    // Fitur tambahan: bayar ulang untuk transaksi yang belum dibayar (status: Menunggu Pembayaran)

    public function bayarUlang(Transaksi $transaksi)
    {
        abort_if($transaksi->user_id !== Auth::id(), 403);

        if ($transaksi->status !== \App\Enums\StatusTransaksi::MenungguPembayaran) {
            return back()->with('error', 'Transaksi tidak bisa dibayar ulang.');
        }

        $pembayaran    = $transaksi->pembayaranUtama;
        $snapTokenLama = $pembayaran?->referensi_midtrans;

        // Snap token Midtrans berupa UUID panjang (bukan nomor transaksi TRX-...)
        // Jika token lama valid → kembalikan langsung tanpa generate baru
        $tokenMasihValid = $snapTokenLama
            && !str_starts_with($snapTokenLama, 'TRX-')
            && strlen($snapTokenLama) > 20;

        if ($tokenMasihValid) {
            return response()->json([
                'snap_token' => $snapTokenLama,
            ]);
        }

        // Token tidak ada / tidak valid → generate baru dengan suffix unik
        Config::$serverKey    = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized  = true;
        Config::$is3ds        = true;

        // Suffix -R{timestamp} membuat order_id unik di Midtrans.
        // MidtransCallbackController::handleUtamaPayment() mengenali format ini:
        //   preg_match('/^(TRX-[A-Z0-9]+-\d+)-R\d+$/', $orderId, $matches)
        //   → lookup transaksi by $matches[1] (nomor asli)
        $orderId = $transaksi->nomor_transaksi . '-R' . time();

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
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit'       => 'hours',
                'duration'   => 24,
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
        } catch (\Exception $e) {
            Log::error('bayarUlang: gagal generate snap token', [
                'transaksi_id' => $transaksi->id,
                'order_id'     => $orderId,
                'error'        => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Gagal mendapatkan token pembayaran: ' . $e->getMessage(),
            ], 500);
        }

        // Simpan token baru agar klik berikutnya tidak generate ulang
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

        return response()->json([
            'snap_token' => $snapToken,
        ]);
    }

    public function bayarDendaLangsung(Denda $denda)
    {
        $denda->load('transaksi');

        abort_if($denda->transaksi->user_id !== Auth::id(), 403);

        Config::$serverKey    = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized  = true;
        Config::$is3ds        = true;

        $orderId = 'DENDA-' . $denda->id . '-' . time();

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $denda->jumlah,
            ],
            'customer_details' => [
                'first_name' => Auth::user()->name,
                'email'      => Auth::user()->email,
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        Pembayaran::updateOrCreate(
            [
                'transaksi_id' => $denda->transaksi_id,
                'jenis'        => 'denda',
                'status'       => 'menunggu',
            ],
            [
                'jumlah'             => $denda->jumlah,
                'metode'             => 'midtrans',
                'referensi_midtrans' => $snapToken,
            ]
        );

        return response()->json([
            'snap_token' => $snapToken,
        ]);
    }

    public function struk(Transaksi $transaksi)
    {
        abort_if($transaksi->user_id !== Auth::id(), 403);
        return view('user.pesanan.struk', compact('transaksi'));
    }
}
