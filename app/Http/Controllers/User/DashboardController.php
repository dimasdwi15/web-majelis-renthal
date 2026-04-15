<?php

namespace App\Http\Controllers\User;

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
                               ->whereIn('status', ['berjalan', 'dibayar'])
                               ->count();
        $menungguBayar   = Transaksi::where('user_id', $user->id)
                               ->where('status', 'menunggu_pembayaran')
                               ->count();
        $tagihanDenda    = Denda::whereHas('transaksi', fn($q) => $q->where('user_id', $user->id))
                               ->whereNull('dibayar_pada')
                               ->count();

        $pesananAktif    = Transaksi::with(['details.barang'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['menunggu_pembayaran', 'dibayar', 'berjalan', 'terlambat'])
            ->latest()
            ->take(5)
            ->get();

        $dendaBelumBayar = Denda::with('transaksi')
            ->whereHas('transaksi', fn($q) => $q->where('user_id', $user->id))
            ->whereNull('dibayar_pada')
            ->latest()
            ->get();

        $riwayatSingkat = Transaksi::where('user_id', $user->id)
            ->whereIn('status', ['selesai', 'dibatalkan'])
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
