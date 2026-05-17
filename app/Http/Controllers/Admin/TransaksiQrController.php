<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TransaksiQrController
 * ─────────────────────
 * Endpoint internal untuk mencari transaksi berdasarkan nomor_transaksi
 * yang terbaca dari QR Code di halaman admin.
 *
 * Route (di routes/web.php):
 *   Route::middleware(['auth', 'verified'])
 *       ->prefix('admin/api')
 *       ->group(function () {
 *           Route::get('/transaksis/find-by-nomor/{nomor}',
 *               [TransaksiQrController::class, 'findByNomor']
 *           )->name('admin.api.transaksis.find-by-nomor');
 *       });
 */
class TransaksiQrController extends Controller
{
    /**
     * Cari transaksi berdasarkan nomor_transaksi dan kembalikan URL detail-nya.
     *
     * @param  Request  $request
     * @param  string   $nomor   Nomor transaksi yang di-scan dari QR Code
     * @return JsonResponse
     */
    public function findByNomor(Request $request, string $nomor): JsonResponse
    {
        $nomorBersih = trim($nomor);

        if (empty($nomorBersih)) {
            return response()->json(
                ['error' => 'Nomor transaksi tidak boleh kosong.'],
                400
            );
        }

        $transaksi = Transaksi::where('nomor_transaksi', $nomorBersih)->first();

        if (! $transaksi) {
            return response()->json(
                ['error' => "Transaksi dengan nomor '{$nomorBersih}' tidak ditemukan."],
                404
            );
        }

        return response()->json([
            'id'              => $transaksi->id,
            'nomor_transaksi' => $transaksi->nomor_transaksi,
            'url'             => route('filament.admin.resources.transaksis.view', [
                'record' => $transaksi->id,
            ]),
        ]);
    }
}
