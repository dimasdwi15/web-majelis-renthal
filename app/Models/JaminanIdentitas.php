<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JaminanIdentitas extends Model
{
    protected $table = 'jaminan_identitas';

    protected $fillable = [
        'transaksi_id',
        'user_id',
        'jenis_identitas',
        'path_file',
        'status',
        'status_ocr',
        'ocr_confidence',
        'perlu_verifikasi_manual',
        'catatan_admin',
        'diverifikasi_pada',
        'dihapus_pada',
    ];

    protected $casts = [
        'perlu_verifikasi_manual' => 'boolean',
        'ocr_confidence'          => 'integer',
        'diverifikasi_pada'       => 'datetime',
        'dihapus_pada'            => 'datetime',
    ];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sudahTerverifikasi(): bool
    {
        return in_array($this->status_ocr, [
            'terverifikasi_otomatis',
            'terverifikasi_manual',
        ]);
    }
}
