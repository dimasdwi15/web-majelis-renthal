<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;

class NotifikasiService
{
    protected FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }
    /**
     * Kirim notifikasi ke seorang user.
     */
    public function kirimKeUser(
        int    $userId,
        string $judul,
        string $pesan,
        string $tipe = 'info',
        array  $data = []
    ): Notifikasi {

        $notif = Notifikasi::create([
            'user_id' => $userId,
            'judul'   => $judul,
            'pesan'   => $pesan,
            'tipe'    => $tipe,
            'data'    => $data,
            'dibaca'  => false,
        ]);

        $user = User::find($userId);

        if ($user && !empty($user->fcm_token)) {

            $this->fcmService->sendToToken(
                fcmToken: $user->fcm_token,
                title: $judul,
                body: $pesan,
                data: $data,
                onInvalidToken: function () use ($user) {
                    $user->update(['fcm_token' => null]);
                },
            );
        }

        return $notif;
    }

    /**
     * Kirim notifikasi ke semua admin.
     */
    public function kirimKeAdmin(
        string $judul,
        string $pesan,
        string $tipe = 'info',
        array  $data = []
    ): void {
        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();

        foreach ($admins as $admin) {
            Notifikasi::create([
                'user_id' => $admin->id,
                'judul'   => $judul,
                'pesan'   => $pesan,
                'tipe'    => $tipe,
                'data'    => $data,
                'dibaca'  => false,
            ]);

            if (!empty($admin->fcm_token)) {
                $this->fcmService->sendToToken(
                    fcmToken: $admin->fcm_token,
                    title: $judul,
                    body: $pesan,
                    data: $data,
                    onInvalidToken: function () use ($admin) {
                        $admin->update(['fcm_token' => null]);
                    },
                );
            }
        }
    }

    /**
     * Kirim notifikasi tagihan denda ke user.
     */
    public function kirimTagihanDenda(
        int    $userId,
        int    $transaksiId,
        int    $dendaId,
        float  $jumlahDenda,
        string $nomorTransaksi
    ): Notifikasi {
        return $this->kirimKeUser(
            userId: $userId,
            judul: 'Tagihan Denda — ' . $nomorTransaksi,
            pesan: 'Anda memiliki tagihan denda sebesar Rp ' . number_format($jumlahDenda, 0, ',', '.') .
                ' untuk transaksi ' . $nomorTransaksi .
                '. Silakan lakukan pembayaran segera.',
            tipe: 'denda',
            data: [
                'transaksi_id' => $transaksiId,
                'denda_id'     => $dendaId,
                'jumlah'       => $jumlahDenda,
            ]
        );
    }

    /**
     * Kirim notifikasi transaksi baru ke admin.
     */
    public function notifTransaksiBaru(
        string $nomorTransaksi,
        string $namaUser,
        int    $transaksiId,
        string $metode
    ): void {
        $this->kirimKeAdmin(
            judul: 'Transaksi Baru — ' . $nomorTransaksi,
            pesan: $namaUser . ' melakukan pemesanan baru via ' . $metode . '. Silakan cek dan proses.',
            tipe: 'transaksi',
            data: [
                'transaksi_id' => $transaksiId,
            ]
        );
    }

    /**
     * Kirim notifikasi status update ke user.
     */
    public function notifStatusUpdate(
        int    $userId,
        int    $transaksiId,
        string $nomorTransaksi,
        string $statusBaru,
        string $pesan
    ): Notifikasi {
        return $this->kirimKeUser(
            userId: $userId,
            judul: 'Update Pesanan — ' . $nomorTransaksi,
            pesan: $pesan,
            tipe: 'pembayaran',
            data: [
                'transaksi_id' => $transaksiId,
                'status'       => $statusBaru,
            ]
        );
    }

    /**
     * Kirim notifikasi pembayaran denda berhasil ke admin.
     */
    public function notifDendaDibayar(
        string $nomorTransaksi,
        string $namaUser,
        int    $transaksiId,
        float  $jumlah
    ): void {
        $this->kirimKeAdmin(
            judul: 'Denda Dibayar — ' . $nomorTransaksi,
            pesan: $namaUser . ' telah membayar denda sebesar Rp ' . number_format($jumlah, 0, ',', '.') . '.',
            tipe: 'pembayaran',
            data: [
                'transaksi_id' => $transaksiId,
            ]
        );
    }
}
