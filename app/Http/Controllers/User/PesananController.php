<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\Denda;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Cek apakah sudah ada snap token tersimpan
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

        // Tunai
        return redirect()
            ->route('user.pesanan.show', $denda->transaksi_id)
            ->with('success', 'Silakan bayar denda secara tunai di toko kami.');
    }

    public function struk(Transaksi $transaksi)
    {
        abort_if($transaksi->user_id !== Auth::id(), 403);
        return view('user.pesanan.struk', compact('transaksi'));
    }
}
