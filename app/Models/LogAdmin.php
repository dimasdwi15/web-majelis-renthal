<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAdmin extends Model
{
    protected $table = 'log_admin';

    protected $fillable = [
        'user_id',
        'aksi',
        'target_tipe',
        'target_id',
        'data_lama',
        'data_baru',
        'ip_address'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
