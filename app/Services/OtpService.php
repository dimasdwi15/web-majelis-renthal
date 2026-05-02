<?php

namespace App\Services;

use App\Mail\OtpVerificationMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Mail;

/**
 * OtpService memusatkan semua logika OTP.
 *
 * Perubahan dari versi sebelumnya:
 * - OTP kini disimpan sebagai bcrypt hash di DB
 * - Verifikasi menggunakan Hash::check() (via $otp->verifyPlain())
 *   bukan hash_equals() — karena hash_equals tidak kompatibel dengan bcrypt
 * - verifyOtp() menggunakan forceFill()->save() agar email_verified_at
 *   pasti tersimpan ke DB
 */
class OtpService
{
    // ─── Send ─────────────────────────────────────────────────────────────────

    /**
     * Kirim OTP berdasarkan email saja — tanpa membutuhkan User model.
     * Digunakan saat pending registration (user belum ada di DB).
     */
    public function sendOtpToEmail(string $email): EmailOtp
    {
        // createForEmail() men-generate plain OTP, menyimpan hash-nya,
        // dan menyisipkan plain OTP ke $otp->plain_otp
        $otp = EmailOtp::createForEmail($email);

        // OtpVerificationMail mengambil plain OTP dari $otp->plain_otp
        Mail::to($email)->send(new OtpVerificationMail($otp));

        return $otp;
    }

    /**
     * Kirim OTP untuk User yang sudah ada di DB.
     * Wrapper dari sendOtpToEmail agar controller lama tetap kompatibel.
     */
    public function sendOtp(User $user): EmailOtp
    {
        return $this->sendOtpToEmail($user->email);
    }

    // ─── Verify ───────────────────────────────────────────────────────────────

    /**
     * Verifikasi OTP berdasarkan email saja (tanpa User model).
     * Digunakan saat pending registration — user belum ada di DB.
     */
    public function verifyOtpByEmail(string $email, string $inputCode): bool
    {
        $otp = EmailOtp::where('email', $email)
            ->where('used', false)
            ->latest()
            ->first();

        if (! $otp || ! $otp->isValid()) {
            return false;
        }

        // Gunakan Hash::check() karena OTP disimpan sebagai bcrypt hash
        if (! $otp->verifyPlain($inputCode)) {
            return false;
        }

        $otp->markAsUsed();

        return true;
    }

    /**
     * Verifikasi OTP untuk User yang sudah ada di DB.
     * Jika valid, tandai email_verified_at langsung ke DB.
     */
    public function verifyOtp(User $user, string $inputCode): bool
    {
        $otp = EmailOtp::where('email', $user->email)
            ->where('used', false)
            ->latest()
            ->first();

        if (! $otp || ! $otp->isValid()) {
            return false;
        }

        // Gunakan Hash::check() — bukan hash_equals() — karena hash bcrypt
        if (! $otp->verifyPlain($inputCode)) {
            return false;
        }

        $otp->markAsUsed();

        // ── Tandai email sebagai terverifikasi ────────────────────────────────
        if (! $user->hasVerifiedEmail()) {
            // forceFill + save: update langsung ke DB, tidak bergantung
            // pada state model di memori (menghindari silent-fail)
            $user->forceFill(['email_verified_at' => now()])->save();

            // Refresh agar model sinkron dengan DB
            $user->refresh();

            // Dispatch event Verified secara eksplisit
            event(new Verified($user));
        }

        return true;
    }

    // ─── Password Reset ───────────────────────────────────────────────────────

    public function sendPasswordResetOtp(string $email): EmailOtp
    {
        $otp = EmailOtp::createForEmail($email);

        Mail::to($email)->send(new PasswordResetOtpMail($otp));

        return $otp;
    }
}
