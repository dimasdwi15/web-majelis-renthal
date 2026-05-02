<?php

namespace App\Services;

use App\Mail\OtpVerificationMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * OtpService memusatkan semua logika OTP.
 * Mendukung dua mode: dengan User model (untuk user yang sudah ada di DB)
 * dan hanya dengan email (untuk pending registration).
 */
class OtpService
{
    /**
     * Kirim OTP berdasarkan email saja — tanpa membutuhkan User model.
     * Digunakan saat pending registration (user belum ada di DB).
     *
     * @return EmailOtp
     */
    public function sendOtpToEmail(string $email): EmailOtp
    {
        // Buat OTP baru (otomatis hapus OTP lama via createForEmail)
        $otp = EmailOtp::createForEmail($email);

        // Kirim email OTP
        Mail::to($email)->send(new OtpVerificationMail($otp));

        return $otp;
    }

    /**
     * Kirim OTP untuk User yang sudah ada di DB.
     * Wrapper dari sendOtpToEmail agar controller lama tetap kompatibel.
     *
     * @return EmailOtp
     */
    public function sendOtp(User $user): EmailOtp
    {
        return $this->sendOtpToEmail($user->email);
    }

    /**
     * Verifikasi OTP berdasarkan email saja (tanpa User model).
     * Digunakan saat pending registration — user belum ada di DB.
     * Tidak memanggil markEmailAsVerified() karena belum ada User.
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

        if (! hash_equals($otp->otp, $inputCode)) {
            return false;
        }

        // Tandai OTP sudah dipakai
        $otp->markAsUsed();

        return true;
    }

    /**
     * Verifikasi OTP untuk User yang sudah ada di DB.
     * Memanggil markEmailAsVerified() setelah OTP valid.
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

        if (! hash_equals($otp->otp, $inputCode)) {
            return false;
        }

        $otp->markAsUsed();

        // Tandai email user sebagai terverifikasi
        $user->markEmailAsVerified();

        return true;
    }
}
