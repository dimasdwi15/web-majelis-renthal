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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

class CheckoutController extends Controller
{
    /**
     * Sinkronisasi data cart di session dengan data terbaru dari database.
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
     *
     * Untuk metode MIDTRANS:
     *   - Request dikirim via AJAX (fetch API)
     *   - Response: JSON { snap_token, redirect_url }
     *   - Frontend membuka popup Midtrans Snap
     *
     * Untuk metode TUNAI (COD):
     *   - Request dikirim via form POST biasa
     *   - Response: redirect ke halaman sukses
     */
    public function proses(Request $request)
    {
        // Deteksi apakah request AJAX (untuk Midtrans)
        $isAjax = $request->expectsJson() || $request->ajax();

        // ── Validasi input ──────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            if ($isAjax) {
                return response()->json([
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // Refresh cart dari DB (harga & stok terbaru)
        $cart = $this->refreshCartSession();

        if (empty($cart)) {
            if ($isAjax) {
                return response()->json(['message' => 'Keranjang kosong.'], 422);
            }
            return redirect()->route('katalog')->with('error', 'Keranjang kosong.');
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
                $msg = "Barang \"{$item['nama']}\" tidak tersedia.";
                if ($isAjax) return response()->json(['message' => $msg], 422);
                return back()->with('error', $msg);
            }

            if ($request->metode_pembayaran === 'midtrans' && $barang->stok < $item['qty']) {
                $msg = "Stok \"{$barang->nama}\" tidak mencukupi (tersisa {$barang->stok}).";
                if ($isAjax) return response()->json(['message' => $msg], 422);
                return back()->with('error', $msg);
            }

            $subtotal   = $barang->harga_per_hari * $item['qty'] * $durasi;
            $totalSewa += $subtotal;

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
                // batas_pembayaran diisi jika kolom ada di DB
                // 'batas_pembayaran'  => now()->addHours(24),
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

                // Stok langsung dikurangi untuk Midtrans (reserve stok).
                // Untuk COD, stok dikurangi saat admin konfirmasi bayar.
                if ($request->metode_pembayaran === 'midtrans') {
                    $detail['barang']->decrement('stok', $detail['qty']);
                }
            }

            // Simpan foto identitas
            $pathFoto = $request->file('foto_identitas')
                ->store('jaminan', 'public');

            JaminanIdentitas::create([
                'transaksi_id'    => $transaksi->id,
                'user_id'         => Auth::id(),
                'jenis_identitas' => $request->jenis_identitas,
                'path_file'       => $pathFoto,
                'status'          => 'aktif',
            ]);

            // Buat record pembayaran utama
            $pembayaran = Pembayaran::create([
                'transaksi_id' => $transaksi->id,
                'jenis'        => 'utama',
                'jumlah'       => $totalSewa,
                'metode'       => $request->metode_pembayaran,
                'status'       => 'menunggu',
            ]);

            // ── MIDTRANS: Generate Snap Token ───────────────────────────
            $snapToken = null;

            if ($request->metode_pembayaran === 'midtrans') {
                Config::$serverKey    = config('midtrans.server_key');
                Config::$isProduction = config('midtrans.is_production');
                Config::$isSanitized  = true;
                Config::$is3ds        = true;

                // Build item details untuk Midtrans
                $itemDetails = [];
                foreach ($itemsValid as $barangId => $detail) {
                    $itemDetails[] = [
                        'id'       => (string) $barangId,
                        'price'    => (int) round($detail['harga_per_hari'] * $durasi),
                        'quantity' => $detail['qty'],
                        'name'     => mb_substr($detail['barang']->nama, 0, 50),
                    ];
                }

                $params = [
                    'transaction_details' => [
                        'order_id'     => $nomorTransaksi,
                        'gross_amount' => (int) $totalSewa,
                    ],
                    'customer_details' => [
                        'first_name' => Auth::user()->name,
                        'email'      => Auth::user()->email,
                        'phone'      => Auth::user()->phone ?? '',
                    ],
                    'item_details' => $itemDetails,
                    // Snap otomatis expired setelah 24 jam
                    'expiry' => [
                        'start_time' => now()->format('Y-m-d H:i:s O'),
                        'unit'       => 'hours',
                        'duration'   => 24,
                    ],
                ];

                $snapToken = Snap::getSnapToken($params);

                // Simpan snap token ke kolom referensi_midtrans
                $pembayaran->update(['referensi_midtrans' => $nomorTransaksi]);
            }

            // Kirim notifikasi ke admin
            $metodeLabel = $request->metode_pembayaran === 'midtrans'
                ? 'Cashless (Midtrans)'
                : 'Tunai (COD)';

            app(\App\Services\NotifikasiService::class)->notifTransaksiBaru(
                nomorTransaksi: $nomorTransaksi,
                namaUser: Auth::user()->name,
                transaksiId: $transaksi->id,
                metode: $metodeLabel
            );

            DB::commit();

            session()->forget('cart');

            // ── Response ────────────────────────────────────────────────
            if ($request->metode_pembayaran === 'midtrans') {
                // Selalu kembalikan JSON — request dari AJAX di checkout.blade.php
                return response()->json([
                    'snap_token'      => $snapToken,
                    'redirect_url'    => route('checkout.sukses', $transaksi->nomor_transaksi),
                    'nomor_transaksi' => $transaksi->nomor_transaksi,
                ]);
            }

            // COD → redirect biasa
            return redirect()->route('checkout.sukses', $transaksi->nomor_transaksi)
                ->with('metode', 'tunai');

        } catch (\Exception $e) {
            DB::rollBack();

            $msg = 'Terjadi kesalahan sistem. Silakan coba lagi. (' . $e->getMessage() . ')';

            if ($isAjax) {
                return response()->json(['message' => $msg], 500);
            }

            return back()->with('error', $msg);
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
