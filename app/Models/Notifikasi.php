<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'user_id',
        'judul',
        'pesan',
        'tipe',
        'data',
        'dibaca',
    ];

    /**
     * Casting attribute types
     */
    protected $casts = [
        'data'   => 'array',
        'dibaca' => 'boolean',
    ];

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: hanya notifikasi belum dibaca
     */
    public function scopeBelumDibaca($query)
    {
        return $query->where('dibaca', false);
    }

    /**
     * Scope: berdasarkan tipe
     */
    public function scopeTipe($query, $tipe)
    {
        return $query->where('tipe', $tipe);
    }

    /**
     * Tandai sebagai sudah dibaca
     */
    public function tandaiDibaca()
    {
        return $this->update(['dibaca' => true]);
    }
}
