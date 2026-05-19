<?php

namespace App\Models;

use App\Observers\NotifikasiObserver;
use App\Services\FcmService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $fillable = [
        'user_id',
        'judul',
        'pesan',
        'tipe',
        'data',
        'dibaca',
        'dibaca_pada',
    ];

    protected $casts = [
        'data'       => 'array',
        'dibaca'     => 'boolean',
        'dibaca_pada' => 'datetime',
    ];

    // ── Daftarkan observer di sini ─────────────────────────────────────────
    // Setiap kali Notifikasi::create() / save() dipanggil → FCM otomatis terkirim
    protected static function booted(): void
    {
        static::observe(NotifikasiObserver::class);
    }

    // ── Relasi ─────────────────────────────────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helper statis: buat notifikasi + otomatis kirim FCM (via observer)
    //
    // Penggunaan di controller / service lain:
    //   Notifikasi::kirim(
    //       userId:     $transaksi->user_id,
    //       judul:      'Pesanan Dikonfirmasi',
    //       pesan:      'Pesanan TRX-xxx telah dikonfirmasi.',
    //       tipe:       'transaksi',
    //       data:       ['transaksi_id' => $transaksi->id, 'status' => 'dikonfirmasi'],
    //   );
    // ─────────────────────────────────────────────────────────────────────
    public static function kirim(
        int    $userId,
        string $judul,
        string $pesan,
        string $tipe  = 'info',
        array  $data  = [],
    ): self {
        return static::create([
            'user_id' => $userId,
            'judul'   => $judul,
            'pesan'   => $pesan,
            'tipe'    => $tipe,
            'data'    => $data,
            'dibaca'  => false,
        ]);
        // Observer NotifikasiObserver::created() akan dipanggil otomatis
        // dan mengirim FCM push ke device user
    }
}
