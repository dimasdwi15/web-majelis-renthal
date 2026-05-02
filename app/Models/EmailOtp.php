<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EmailOtp extends Model
{
    protected $fillable = [
        'email',
        'otp',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used'       => 'boolean',
    ];

    /**
     * Cek apakah OTP masih valid (belum expired & belum dipakai).
     */
    public function isValid(): bool
    {
        return ! $this->used
            && $this->expires_at->isFuture();
    }

    /**
     * Tandai OTP sebagai sudah dipakai.
     */
    public function markAsUsed(): void
    {
        $this->update(['used' => true]);
    }

    /**
     * Generate OTP 6 digit acak.
     */
    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Buat OTP baru untuk email tertentu.
     * OTP lama yang belum dipakai akan dihapus terlebih dahulu
     * agar tidak menumpuk di database.
     */
    public static function createForEmail(string $email): self
    {
        // Hapus OTP lama untuk email ini
        static::where('email', $email)->delete();

        return static::create([
            'email'      => $email,
            'otp'        => static::generateCode(),
            'expires_at' => Carbon::now()->addMinutes(10), // berlaku 10 menit
        ]);
    }
}
