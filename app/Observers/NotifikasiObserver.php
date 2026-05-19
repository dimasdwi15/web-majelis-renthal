<?php

namespace App\Observers;

use App\Models\Notifikasi;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

/**
 * NotifikasiObserver
 *
 * Dipicu otomatis setiap kali record Notifikasi dibuat.
 * Langsung mengirim FCM push ke device user yang bersangkutan.
 *
 * Cara kerja:
 *   1. Notifikasi disimpan ke DB (sudah terjadi sebelum observer dipanggil)
 *   2. Observer ambil fcm_token dari relasi user
 *   3. Kirim FCM push via FcmService
 *   4. Jika FCM gagal → hanya log error, tidak rollback simpan notifikasi
 */
class NotifikasiObserver
{
    public function __construct(private readonly FcmService $fcm) {}

    // ── Dipanggil setelah INSERT berhasil ─────────────────────────────────
    public function created(Notifikasi $notifikasi): void
    {
        try {
            // Eager load user beserta fcm_token
            $user = $notifikasi->user;

            if (! $user) {
                Log::warning('[FCM Observer] User tidak ditemukan untuk notifikasi', [
                    'notifikasi_id' => $notifikasi->id,
                    'user_id'       => $notifikasi->user_id,
                ]);
                return;
            }

            if (empty($user->fcm_token)) {
                Log::info('[FCM Observer] User belum punya FCM token, skip push', [
                    'user_id' => $user->id,
                ]);
                return;
            }

            // ── Siapkan data payload untuk navigasi di Flutter ──────────
            $data = [
                'notifikasi_id' => (string) $notifikasi->id,
                'tipe'          => $notifikasi->tipe,
            ];

            // Sertakan transaksi_id jika ada di kolom data JSON
            if (! empty($notifikasi->data['transaksi_id'])) {
                $data['transaksi_id'] = (string) $notifikasi->data['transaksi_id'];
            }

            // ── Pilih channel berdasarkan tipe notifikasi ───────────────
            $channelId = match ($notifikasi->tipe) {
                'pengingat'  => 'majelis_reminder',
                default      => 'majelis_notif',
            };

            // ── Kirim FCM push ──────────────────────────────────────────
            $this->fcm->sendToToken(
                fcmToken: $user->fcm_token,
                title: $notifikasi->judul,
                body: $notifikasi->pesan,
                data: $data,
                channelId: $channelId,
                onInvalidToken: function (string $token) use ($user) {
                    // Token tidak valid → hapus dari DB agar tidak dipakai lagi
                    if ($user->fcm_token === $token) {
                        $user->update(['fcm_token' => null]);
                        Log::info('[FCM Observer] Token dihapus dari DB', ['user_id' => $user->id]);
                    }
                },
            );
        } catch (\Throwable $e) {
            // Jangan sampai gagal FCM membatalkan proses bisnis utama
            Log::error('[FCM Observer] Exception: ' . $e->getMessage(), [
                'notifikasi_id' => $notifikasi->id,
            ]);
        }
    }
}
