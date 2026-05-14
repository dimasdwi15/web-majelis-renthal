<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatHistory extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'role',
        'message',
        'need_admin',
        'whatsapp_url',
        'tokens_used',
        'model',
        'attached_product_id',
    ];

    protected $casts = [
        'need_admin' => 'boolean',
        'tokens_used' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachedProduct(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'attached_product_id');
    }
}
