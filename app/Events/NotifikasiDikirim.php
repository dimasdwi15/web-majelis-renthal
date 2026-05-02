<?php

namespace App\Events;

use App\Models\Notifikasi;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event ini di-broadcast SETELAH notifikasi disimpan ke database.
 * Menggunakan ShouldBroadcastNow agar tidak perlu queue worker
 * (langsung dikirim ke Pusher secara synchronous).
 *
 * Jika kamu menggunakan queue di production dan ingin notifikasi
 * tetap real-time, ganti ke ShouldBroadcast dan pastikan
 * queue:work berjalan.
 */
class NotifikasiDikirim implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Notifikasi $notifikasi
    ) {}

    /**
     * Private channel — hanya user yang memiliki user_id
     * yang cocok yang bisa subscribe dan mendengar event ini.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifikasi.' . $this->notifikasi->user_id),
        ];
    }

    /**
     * Nama event yang akan didengar oleh Laravel Echo di frontend.
     * Prefix dot (.) di frontend menandakan event custom (bukan built-in).
     */
    public function broadcastAs(): string
    {
        return 'notifikasi.masuk';
    }

    /**
     * Payload yang dikirim ke browser user.
     * Hanya kirim data yang diperlukan frontend — jangan expose data sensitif.
     */
    public function broadcastWith(): array
    {
        return [
            'id'          => $this->notifikasi->id,
            'judul'       => $this->notifikasi->judul,
            'pesan'       => $this->notifikasi->pesan,
            'tipe'        => $this->notifikasi->tipe,
            'data'        => $this->notifikasi->data ?? [],
            'dibaca_pada' => $this->notifikasi->dibaca_pada,
            'waktu'       => $this->notifikasi->created_at->diffForHumans(),
        ];
    }
}
