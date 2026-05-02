<?php

namespace App\Mail;

use App\Models\EmailOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * $otp dioper dari controller, lalu tersedia di view Blade.
     */
    public function __construct(
        public readonly EmailOtp $otp
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Majelis Rental] Kode Verifikasi Email Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            // View Blade email — kita buat di langkah berikutnya
            view: 'emails.otp-verification',
        );
    }
}
