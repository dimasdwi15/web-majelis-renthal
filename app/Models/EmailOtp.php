<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EmailOtp extends Model
{
    protected $fillable = [
        'email',
        'otp',       // disimpan sebagai bcrypt hash
        'expires_at',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used'       => 'boolean',
    ];

    // ─── Factory method ───────────────────────────────────────────────────────

    /**
     * Buat OTP baru untuk email tertentu.
     *
     * - Generate kode plain 6 digit
     * - Hash kode sebelum disimpan ke DB
     * - Hapus semua OTP lama milik email ini
     * - Kembalikan instance dengan ->plain_otp agar bisa dikirim via email
     *
     * @return static  (dengan property sementara $plain_otp)
     */
    public static function createForEmail(string $email): static
    {
        // Hapus OTP lama milik email ini
        static::where('email', $email)->delete();

        // Generate kode 6 digit plain
        $plainOtp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Simpan versi hash-nya ke DB
        $instance = static::create([
            'email'      => $email,
            'otp'        => Hash::make($plainOtp),
            'expires_at' => now()->addMinutes(10),
            'used'       => false,
        ]);

        // Sisipkan plain OTP ke instance (tidak disimpan ke DB)
        // agar OtpService bisa meneruskannya ke Mailable
        $instance->plain_otp = $plainOtp;

        return $instance;
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    /**
     * Cek apakah OTP masih berlaku (belum expired dan belum dipakai).
     */
    public function isValid(): bool
    {
        return ! $this->used && $this->expires_at->isFuture();
    }

    /**
     * Tandai OTP sudah dipakai.
     */
    public function markAsUsed(): void
    {
        $this->update(['used' => true]);
    }

    /**
     * Verifikasi kode plain yang diinput user terhadap hash di DB.
     * Gunakan method ini alih-alih hash_equals() langsung.
     */
    public function verifyPlain(string $plainCode): bool
    {
        return Hash::check($plainCode, $this->otp);
    }
}
