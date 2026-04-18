<?php

namespace App\Http\Controllers\User;

use App\Enums\StatusTransaksi;
use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\Denda;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $totalPesanan    = Transaksi::where('user_id', $user->id)->count();
        $pesananBerjalan = Transaksi::where('user_id', $user->id)
                               ->whereIn('status', [
                                   StatusTransaksi::Berjalan,
                                   StatusTransaksi::Dibayar,
                               ])
                               ->count();
        $menungguBayar   = Transaksi::where('user_id', $user->id)
                               ->where('status', StatusTransaksi::MenungguPembayaran)
                               ->count();
        $tagihanDenda    = Denda::whereHas('transaksi', fn($q) => $q->where('user_id', $user->id))
                               ->whereNull('dibayar_pada')
                               ->count();

        $pesananAktif    = Transaksi::with(['details.barang'])
            ->where('user_id', $user->id)
            ->whereIn('status', [
                StatusTransaksi::MenungguPembayaran,
                StatusTransaksi::Dibayar,
                StatusTransaksi::Berjalan,
                StatusTransaksi::Terlambat,
                StatusTransaksi::Dikembalikan,
            ])
            ->latest()
            ->take(5)
            ->get();

        $dendaBelumBayar = Denda::with('transaksi')
            ->whereHas('transaksi', fn($q) => $q->where('user_id', $user->id))
            ->whereNull('dibayar_pada')
            ->latest()
            ->get();

        $riwayatSingkat = Transaksi::where('user_id', $user->id)
            ->whereIn('status', [
                StatusTransaksi::Selesai,
                StatusTransaksi::Dibatalkan,
            ])
            ->latest()
            ->take(5)
            ->get();

        return view('user.dashboard', compact(
            'totalPesanan',
            'pesananBerjalan',
            'menungguBayar',
            'tagihanDenda',
            'pesananAktif',
            'dendaBelumBayar',
            'riwayatSingkat'
        ));
    }
}

