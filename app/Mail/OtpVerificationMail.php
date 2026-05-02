<?php

namespace App\Mail;

use App\Models\EmailOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class OtpVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $plainCode;
    public string $email;
    public Carbon $expiresAt;

    /**
     * Ambil data yang dibutuhkan view dari instance EmailOtp.
     *
     * - $plainCode  → kode 6 digit plain (dari $otp->plain_otp yang disisipkan
     *                 oleh EmailOtp::createForEmail(), tidak disimpan ke DB)
     * - $email      → alamat email tujuan
     * - $expiresAt  → waktu kedaluwarsa OTP (Carbon instance)
     */
    public function __construct(EmailOtp $otp)
    {
        $this->plainCode = $otp->plain_otp ?? '';
        $this->email     = $otp->email;
        $this->expiresAt = $otp->expires_at;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Majelis Rental] Kode Verifikasi Email Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp-verification',
            // Ketiga variabel di bawah otomatis tersedia di Blade
            // sebagai $plainCode, $email, $expiresAt
            with: [
                'plainCode'  => $this->plainCode,
                'email'      => $this->email,
                'expiresAt'  => $this->expiresAt,
            ],
        );
    }
}
