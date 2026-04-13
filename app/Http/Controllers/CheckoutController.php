<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\JaminanIdentitas;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Sinkronisasi data cart di session dengan data terbaru dari database.
     * Logika identik dengan KeranjangController::refreshCartSession().
     * Dijalankan sebelum halaman checkout dirender agar semua data akurat.
     *
     * @return array  Cart yang sudah di-refresh
     */
    private function refreshCartSession(): array
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return $cart;
        }

        $barangList = Barang::with('fotoUtama')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        foreach ($cart as $id => $item) {
            $barang = $barangList->get($id);

            if (!$barang || $barang->status !== 'aktif') {
                unset($cart[$id]);
                continue;
            }

            $cart[$id]['nama']  = $barang->nama;
            $cart[$id]['harga'] = (float) $barang->harga_per_hari;
            $cart[$id]['stok']  = $barang->stok;
            $cart[$id]['foto']  = $barang->fotoUtama?->path_foto;

            if ($cart[$id]['qty'] > $barang->stok) {
                $cart[$id]['qty'] = max(1, $barang->stok);
            }
        }

        session(['cart' => $cart]);

        return $cart;
    }

    /**
     * Tampilkan halaman checkout.
     * Refresh data cart dari DB terlebih dahulu agar harga/stok akurat.
     * Redirect ke katalog jika keranjang kosong (termasuk setelah refresh).
     */
    public function index()
    {
        $cart = $this->refreshCartSession();

        if (empty($cart)) {
            return redirect()->route('katalog')
                ->with('error', 'Keranjang Anda kosong. Silakan pilih barang terlebih dahulu.');
        }

        return view('user.pages.checkout');
    }

    /**
     * Proses submit checkout.
     */
    public function proses(Request $request)
    {
        // ── Validasi input ──────────────────────────────────────────────
        $request->validate([
            'tanggal_ambil'     => ['required', 'date', 'after_or_equal:today'],
            'tanggal_kembali'   => ['required', 'date', 'after:tanggal_ambil'],
            'metode_pembayaran' => ['required', 'in:midtrans,tunai'],
            'jenis_identitas'   => ['required', 'in:KTP,SIM,PELAJAR'],
            'foto_identitas'    => ['required', 'image', 'max:5120'],
        ], [
            'tanggal_ambil.after_or_equal'  => 'Tanggal ambil minimal hari ini.',
            'tanggal_kembali.after'         => 'Tanggal kembali harus setelah tanggal ambil.',
            'foto_identitas.required'       => 'Foto identitas wajib diupload.',
            'foto_identitas.image'          => 'File harus berupa gambar (JPG/PNG/WEBP).',
            'foto_identitas.max'            => 'Ukuran foto maksimal 5MB.',
        ]);

        // Gunakan data cart yang sudah di-refresh (harga terbaru dari DB)
        $cart = $this->refreshCartSession();

        if (empty($cart)) {
            return redirect()->route('katalog')
                ->with('error', 'Keranjang kosong.');
        }

        $tglAmbil   = Carbon::parse($request->tanggal_ambil);
        $tglKembali = Carbon::parse($request->tanggal_kembali);
        $durasi     = $tglAmbil->diffInDays($tglKembali);

        // ── Hitung total & validasi stok ───────────────────────────────
        $totalSewa  = 0;
        $itemsValid = [];

        foreach ($cart as $barangId => $item) {
            $barang = Barang::find($barangId);

            if (!$barang || $barang->status !== 'aktif') {
                return back()->with('error', "Barang \"{$item['nama']}\" tidak tersedia.");
            }

            if ($request->metode_pembayaran === 'midtrans' && $barang->stok < $item['qty']) {
                return back()->with('error', "Stok \"{$barang->nama}\" tidak mencukupi (tersisa {$barang->stok}).");
            }

            $subtotal    = $barang->harga_per_hari * $item['qty'] * $durasi;
            $totalSewa  += $subtotal;

            $itemsValid[$barangId] = [
                'barang'         => $barang,
                'qty'            => $item['qty'],
                'harga_per_hari' => $barang->harga_per_hari,
                'subtotal'       => $subtotal,
            ];
        }

        // ── Buat transaksi dalam DB transaction ────────────────────────
        DB::beginTransaction();

        try {
            $nomorTransaksi = 'TRX-' . strtoupper(Str::random(8)) . '-' . now()->format('ymd');

            $transaksi = Transaksi::create([
                'user_id'           => Auth::id(),
                'nomor_transaksi'   => $nomorTransaksi,
                'status'            => 'menunggu_pembayaran',
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_pembayaran' => 'menunggu',
                'total_sewa'        => $totalSewa,
                'total_denda'       => 0,
                'total_charge'      => 0,
                'tanggal_ambil'     => $tglAmbil->toDateString(),
                'tanggal_kembali'   => $tglKembali->toDateString(),
            ]);

            foreach ($itemsValid as $barangId => $detail) {
                TransaksiDetail::create([
                    'transaksi_id'   => $transaksi->id,
                    'barang_id'      => $barangId,
                    'jumlah'         => $detail['qty'],
                    'harga_per_hari' => $detail['harga_per_hari'],
                    'durasi_hari'    => $durasi,
                    'subtotal'       => $detail['subtotal'],
                ]);

                if ($request->metode_pembayaran === 'midtrans') {
                    $detail['barang']->decrement('stok', $detail['qty']);
                }
            }

            $pathFoto = $request->file('foto_identitas')
                ->store('jaminan', 'public');

            JaminanIdentitas::create([
                'transaksi_id'    => $transaksi->id,
                'user_id'         => Auth::id(),
                'jenis_identitas' => $request->jenis_identitas,
                'path_file'       => $pathFoto,
                'status'          => 'aktif',
            ]);

            Pembayaran::create([
                'transaksi_id' => $transaksi->id,
                'jenis'        => 'utama',
                'jumlah'       => $totalSewa,
                'metode'       => $request->metode_pembayaran,
                'status'       => 'menunggu',
            ]);

            DB::commit();

            session()->forget('cart');

            if ($request->metode_pembayaran === 'midtrans') {
                return redirect()->route('checkout.sukses', $transaksi->nomor_transaksi)
                    ->with('metode', 'midtrans');
            } else {
                return redirect()->route('checkout.sukses', $transaksi->nomor_transaksi)
                    ->with('metode', 'tunai');
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi. (' . $e->getMessage() . ')');
        }
    }

    /**
     * Halaman sukses / struk setelah checkout.
     */
    public function sukses($nomorTransaksi)
    {
        $transaksi = Transaksi::with(['details.barang', 'jaminanIdentitas'])
            ->where('nomor_transaksi', $nomorTransaksi)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('user.pages.checkout-sukses', compact('transaksi'));
    }
}
