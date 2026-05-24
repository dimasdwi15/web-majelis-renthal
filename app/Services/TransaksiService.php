<?php

namespace App\Services;

use App\Enums\MetodePembayaran;
use App\Enums\StatusTransaksi;
use App\Events\NotifikasiDikirim;
use App\Models\BarangRusak;
use App\Models\Denda;
use App\Models\DendaFoto;
use App\Models\Notifikasi;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Midtrans\Config;
use Midtrans\Snap;

class TransaksiService
{
    protected NotifikasiService $notifikasi;
    protected \App\Services\RewardsService $rewards;

    public function __construct(NotifikasiService $notifikasi, \App\Services\RewardsService $rewards)
    {
        $this->notifikasi = $notifikasi;
        $this->rewards    = $rewards;
    }

    // ── 0. AUTO-MARK TERLAMBAT ──────────────────────────────────────────
    public function markTerlambat(): int
    {
        $transaksiTerlambat = Transaksi::where('status', StatusTransaksi::Berjalan)
            ->whereDate('tanggal_kembali', '<', now()->toDateString())
            ->get();

        foreach ($transaksiTerlambat as $transaksi) {
            $transaksi->update(['status' => StatusTransaksi::Terlambat]);

            $this->notifikasi->notifStatusUpdate(
                userId: $transaksi->user_id,
                transaksiId: $transaksi->id,
                nomorTransaksi: $transaksi->nomor_transaksi,
                statusBaru: 'terlambat',
                pesan: 'Batas waktu pengembalian sudah terlewati! Segera kembalikan barang untuk menghindari denda tambahan. '
                    .'Denda keterlambatan akan dihitung sebesar 50% dari total sewa (Rp '
                    .number_format($transaksi->total_sewa * 0.5, 0, ',', '.').').'
            );
        }

        return $transaksiTerlambat->count();
    }

    // ── 1. BAYAR COD ───────────────────────────────────────────────
    public function bayarCod(Transaksi $transaksi): void
    {
        DB::transaction(function () use ($transaksi) {
            $transaksi->pembayaranUtama?->update([
                'status'     => 'lunas',
                'dibayar_pada' => now(),
            ]);

            $transaksi->update([
                'status'            => StatusTransaksi::Berjalan,
                'status_pembayaran' => 'lunas',
                'tanggal_ambil'     => now(),
            ]);

            foreach ($transaksi->details as $detail) {
                $detail->barang->decrement('stok', $detail->jumlah);
            }

            $this->notifikasi->notifStatusUpdate(
                userId: $transaksi->user_id,
                transaksiId: $transaksi->id,
                nomorTransaksi: $transaksi->nomor_transaksi,
                statusBaru: 'berjalan',
                pesan: 'Pembayaran tunai dikonfirmasi. Barang telah diambil. Selamat menggunakan!'
            );
        });

        // ── Award XP setelah status jadi Berjalan (COD) ──────────────
        // XP untuk COD diberikan setelah user datang ke basecamp & admin konfirmasi
        $transaksi->refresh();
        $this->rewards->awardCheckoutXp($transaksi);
    }

    // ── 2. AMBIL BARANG (Cashless — opsional, jika admin perlu konfirmasi) ─
    public function ambilBarang(Transaksi $transaksi): void
    {
        $transaksi->update([
            'status' => StatusTransaksi::Berjalan,
            'tanggal_ambil' => now(),
        ]);

        $this->notifikasi->notifStatusUpdate(
            userId: $transaksi->user_id,
            transaksiId: $transaksi->id,
            nomorTransaksi: $transaksi->nomor_transaksi,
            statusBaru: 'berjalan',
            pesan: 'Barang telah diambil. Pastikan dikembalikan sebelum '
                .$transaksi->tanggal_kembali->format('d M Y').'.'
        );
    }

    // ── 3. PROSES PENGEMBALIAN ──────────────────────────────────────────
    /**
     * @param  array<int, int>  $jumlahRusakPerDetailId  key = transaksi_detail.id, value = qty rusak
     */
    public function prosesKembali(
        Transaksi $transaksi,
        float $dendaKerusakan = 0,
        string $catatan = '',
        array $fotoFiles = [],
        array $jumlahRusakPerDetailId = [],
    ): void {
        DB::transaction(function () use ($transaksi, $dendaKerusakan, $catatan, $fotoFiles, $jumlahRusakPerDetailId) {
            $totalDenda = 0;

            $transaksi->load('details.barang');
            $transaksi->tanggal_dikembalikan = now();

            $totalUnitRusak = 0;
            foreach ($transaksi->details as $detail) {
                $rusak = max(0, (int) ($jumlahRusakPerDetailId[$detail->id] ?? 0));
                if ($rusak > $detail->jumlah) {
                    throw ValidationException::withMessages([
                        "kerusakan_detail_{$detail->id}" => "Maksimal {$detail->jumlah} unit untuk baris ini.",
                    ]);
                }
                $totalUnitRusak += $rusak;
            }

            if ($dendaKerusakan > 0 && $totalUnitRusak === 0) {
                throw ValidationException::withMessages([
                    'dendaKerusakan' => 'Tentukan jumlah unit rusak minimal 1 pada salah satu barang yang disewa.',
                ]);
            }

            if ($dendaKerusakan <= 0 && $totalUnitRusak > 0) {
                throw ValidationException::withMessages([
                    'dendaKerusakan' => 'Isi nominal denda kerusakan jika ada barang rusak.',
                ]);
            }

            $dendaTelat = $transaksi->hitung_denda_telat;
            if ($dendaTelat > 0) {
                Denda::create([
                    'transaksi_id' => $transaksi->id,
                    'jenis' => 'terlambat',
                    'jumlah' => $dendaTelat,
                    'catatan' => 'Keterlambatan '.$transaksi->hari_telat.' hari · denda otomatis 50% dari total sewa',
                    'dibuat_oleh' => Auth::id(),
                ]);
                $totalDenda += $dendaTelat;
            }

            $dendaRusak = null;
            if ($dendaKerusakan > 0) {
                $dendaRusak = Denda::create([
                    'transaksi_id' => $transaksi->id,
                    'jenis' => 'kerusakan',
                    'jumlah' => $dendaKerusakan,
                    'catatan' => $catatan,
                    'dibuat_oleh' => Auth::id(),
                ]);

                foreach ($fotoFiles as $file) {
                    $path = $file->store('denda', 'public');
                    DendaFoto::create([
                        'denda_id' => $dendaRusak->id,
                        'path_foto' => $path,
                    ]);
                }

                $totalDenda += $dendaKerusakan;
            }

            $transaksi->update([
                'status' => StatusTransaksi::Dikembalikan,
                'total_denda' => $totalDenda,
                'total_charge' => $totalDenda,
                'tanggal_dikembalikan' => now(),
            ]);

            foreach ($transaksi->details as $detail) {
                $rusak = max(0, (int) ($jumlahRusakPerDetailId[$detail->id] ?? 0));
                $baik = $detail->jumlah - $rusak;

                if ($baik > 0) {
                    $detail->barang->increment('stok', $baik);
                }

                if ($rusak > 0) {
                    BarangRusak::create([
                        'transaksi_id' => $transaksi->id,
                        'transaksi_detail_id' => $detail->id,
                        'barang_id' => $detail->barang_id,
                        'denda_id' => $dendaRusak?->id,
                        'jumlah' => $rusak,
                        'status' => BarangRusak::STATUS_MENUNGGU,
                        'catatan_kerusakan' => $catatan !== '' ? $catatan : null,
                        'dibuat_oleh' => Auth::id(),
                    ]);
                }
            }

            if ($totalDenda <= 0) {
                $transaksi->update(['status' => StatusTransaksi::Selesai]);

                $this->notifikasi->notifStatusUpdate(
                    userId: $transaksi->user_id,
                    transaksiId: $transaksi->id,
                    nomorTransaksi: $transaksi->nomor_transaksi,
                    statusBaru: 'selesai',
                    pesan: 'Barang telah dikembalikan. Transaksi selesai. Terima kasih!'
                );
            } else {
                $this->notifikasi->notifStatusUpdate(
                    userId: $transaksi->user_id,
                    transaksiId: $transaksi->id,
                    nomorTransaksi: $transaksi->nomor_transaksi,
                    statusBaru: 'dikembalikan',
                    pesan: 'Barang telah dikembalikan. Terdapat tagihan denda sebesar Rp '
                        .number_format($totalDenda, 0, ',', '.').'. Silakan selesaikan pembayaran.'
                );
            }
        });
    }

    /**
     * Setelah perbaikan fisik: tambah stok sewa dan tutup entri barang rusak.
     */
    public function kembalikanBarangRusakKeStok(BarangRusak $barangRusak): void
    {
        if ($barangRusak->status !== BarangRusak::STATUS_MENUNGGU) {
            throw ValidationException::withMessages([
                'status' => 'Hanya entri yang masih menunggu perbaikan yang bisa dikembalikan ke stok.',
            ]);
        }

        DB::transaction(function () use ($barangRusak) {
            $barangRusak->barang->increment('stok', $barangRusak->jumlah);

            $barangRusak->update([
                'status' => BarangRusak::STATUS_DIPERBAIKI,
                'diperbaiki_pada' => now(),
                'diperbaiki_oleh' => Auth::id(),
            ]);
        });
    }

    // ── 4. BAYAR DENDA COD ──────────────────────────────────────────────
    public function bayarDendaCod(Transaksi $transaksi): void
    {
        DB::transaction(function () use ($transaksi) {
            $transaksi->denda()->whereNull('dibayar_pada')->update([
                'dibayar_pada' => now(),
            ]);

            Pembayaran::create([
                'transaksi_id' => $transaksi->id,
                'jenis' => 'denda',
                'jumlah' => $transaksi->total_denda,
                'metode' => 'tunai',
                'status' => 'lunas',
                'dibayar_pada' => now(),
            ]);

            $transaksi->update([
                'status' => StatusTransaksi::Selesai,
                'status_pembayaran' => 'lunas',
            ]);

            $this->notifikasi->notifStatusUpdate(
                userId: $transaksi->user_id,
                transaksiId: $transaksi->id,
                nomorTransaksi: $transaksi->nomor_transaksi,
                statusBaru: 'selesai',
                pesan: 'Pembayaran denda tunai dikonfirmasi. Transaksi selesai. Terima kasih!'
            );
        });
    }

    // ── 5. KIRIM TAGIHAN DENDA (Cashless via Midtrans) ─────────────────
    //
    //  ✅ PERUBAHAN:
    //     Setelah notifikasi disimpan ke DB via NotifikasiService,
    //     kita fetch record-nya lalu broadcast real-time ke browser user
    //     menggunakan event NotifikasiDikirim + Pusher.
    //
    public function kirimTagihan(Transaksi $transaksi): string
    {
        // ── A. Konfigurasi & generate Snap Token Midtrans ───────────────
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $totalDenda = (float) $transaksi->total_denda;

        $params = [
            'transaction_details' => [
                'order_id' => 'CHARGE-'.$transaksi->id.'-'.time(),
                'gross_amount' => (int) $totalDenda,
            ],
            'customer_details' => [
                'first_name' => $transaksi->user->name,
                'email' => $transaksi->user->email,
                'phone' => $transaksi->user->phone ?? '',
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        // ── B. Simpan / update record pembayaran denda ──────────────────
        Pembayaran::updateOrCreate(
            [
                'transaksi_id' => $transaksi->id,
                'jenis' => 'denda',
                'status' => 'menunggu',
            ],
            [
                'jumlah' => $totalDenda,
                'metode' => 'midtrans',
                'referensi_midtrans' => $snapToken,
            ]
        );

        // ── C. Simpan notifikasi ke database via NotifikasiService ──────
        //      (tetap seperti semula — tidak diubah)
        $dendaTerbaru = $transaksi->denda()->latest()->first();

        $this->notifikasi->kirimTagihanDenda(
            userId: $transaksi->user_id,
            transaksiId: $transaksi->id,
            dendaId: $dendaTerbaru?->id ?? 0,
            jumlahDenda: $totalDenda,
            nomorTransaksi: $transaksi->nomor_transaksi
        );

        // ── D. ✅ BARU: Broadcast real-time ke browser user ─────────────
        //
        //      Ambil notifikasi yang BARU SAJA dibuat oleh kirimTagihanDenda()
        //      di atas, lalu kirim event ke Pusher channel milik user.
        //
        //      Menggunakan query latest() karena NotifikasiService::kirimTagihanDenda()
        //      tidak mengembalikan model — ini cara paling aman tanpa
        //      mengubah signature NotifikasiService.
        //
        $notifikasiTerbaru = Notifikasi::where('user_id', $transaksi->user_id)
            ->where('tipe', 'denda')
            ->latest()
            ->first();

        if ($notifikasiTerbaru) {
            broadcast(new NotifikasiDikirim($notifikasiTerbaru));
        }

        return $snapToken;
    }

    // ── 6. SELESAIKAN TRANSAKSI (dipanggil dari Midtrans callback denda) ─
    public function selesaikanTransaksi(Transaksi $transaksi): void
    {
        DB::transaction(function () use ($transaksi) {

            // pastikan semua denda sudah dibayar
            $semuaDendaLunas = $transaksi->denda()
                ->whereNull('dibayar_pada')
                ->count() === 0;

            if (! $semuaDendaLunas) {
                return;
            }

            $transaksi->update([
                'status' => StatusTransaksi::Selesai,
                'status_pembayaran' => 'lunas',
            ]);

            $this->notifikasi->notifStatusUpdate(
                userId: $transaksi->user_id,
                transaksiId: $transaksi->id,
                nomorTransaksi: $transaksi->nomor_transaksi,
                statusBaru: 'selesai',
                pesan: 'Pembayaran denda berhasil. Transaksi selesai.'
            );
        });
    }

    // ── 7. AUTO-CANCEL COD EXPIRED ──────────────────────────────────────
    // Deadline COD = H+1 tanggal_ambil (user tidak datang).
    public function cancelExpiredCod(): int
    {
        $expired = Transaksi::where('status', StatusTransaksi::MenungguPembayaran)
            ->where('metode_pembayaran', MetodePembayaran::Tunai)
            ->where('batas_pembayaran', '<', now())
            ->get();

        foreach ($expired as $transaksi) {
            $transaksi->update([
                'status' => StatusTransaksi::Dibatalkan,
                'status_pembayaran' => 'gagal',
            ]);

            $this->notifikasi->notifStatusUpdate(
                userId: $transaksi->user_id,
                transaksiId: $transaksi->id,
                nomorTransaksi: $transaksi->nomor_transaksi,
                statusBaru: 'dibatalkan',
                pesan: 'Pesanan dibatalkan otomatis karena pembayaran COD tidak dilakukan dalam 24 jam.'
            );
        }

        return $expired->count();
    }

    // ── 8. AUTO-CANCEL MIDTRANS EXPIRED ────────────────────────────────
    // Batalkan transaksi Midtrans yang belum dibayar setelah 24 jam
    // (dihitung dari created_at karena kolom batas_pembayaran opsional).
    public function cancelExpiredMidtrans(): int
    {
        $expired = Transaksi::where('status', StatusTransaksi::MenungguPembayaran)
            ->where('metode_pembayaran', MetodePembayaran::Midtrans)
            ->where('batas_pembayaran', '<', now())
            ->get();

        foreach ($expired as $transaksi) {
            // Restore stok — karena stok sudah dikurangi saat checkout Midtrans
            foreach ($transaksi->details as $detail) {
                $detail->barang->increment('stok', $detail->jumlah);
            }

            $transaksi->update([
                'status' => StatusTransaksi::Dibatalkan,
                'status_pembayaran' => 'gagal',
            ]);

            // Tandai pembayaran gagal
            $transaksi->pembayaranUtama?->update(['status' => 'gagal']);

            $this->notifikasi->notifStatusUpdate(
                userId: $transaksi->user_id,
                transaksiId: $transaksi->id,
                nomorTransaksi: $transaksi->nomor_transaksi,
                statusBaru: 'dibatalkan',
                pesan: 'Pesanan dibatalkan otomatis karena pembayaran Midtrans tidak diselesaikan dalam 24 jam. '
                    .'Stok barang telah dikembalikan.'
            );
        }

        return $expired->count();
    }
}
