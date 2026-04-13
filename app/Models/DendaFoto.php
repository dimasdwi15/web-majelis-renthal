<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DendaFoto extends Model
{
    protected $table = 'denda_foto';

    protected $fillable = [
        'denda_id',
        'path_foto'
    ];

    public function denda()
    {
        return $this->belongsTo(Denda::class, 'denda_id');
    }
}
