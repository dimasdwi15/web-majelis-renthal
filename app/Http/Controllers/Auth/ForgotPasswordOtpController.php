<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordOtpController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Tampilkan halaman input kode OTP reset password.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $email = session('password_reset_email');

        // Tidak ada session → paksa kembali ke halaman forgot password
        if (! $email) {
            return redirect()->route('password.request')
                ->with('error', 'Sesi tidak ditemukan. Silakan masukkan email Anda kembali.');
        }

        return view('auth.forgot-password-otp', compact('email'));
    }

    /**
     * Verifikasi kode OTP.
     * Jika valid → buat reset token Laravel, redirect ke halaman reset password.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $email = session('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request')
                ->with('error', 'Sesi tidak ditemukan. Silakan ulangi dari awal.');
        }

        // Verifikasi OTP berdasarkan email
        if (! $this->otpService->verifyOtpByEmail($email, $request->otp)) {
            return back()->withErrors([
                'otp' => 'Kode OTP tidak valid atau sudah kadaluarsa. Silakan minta kode baru.'
            ]);
        }

        // OTP valid — cari user
        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Email tidak terdaftar.']);
        }

        // Buat reset token Laravel yang sah
        $token = Password::createToken($user);

        // Hapus session email reset
        session()->forget('password_reset_email');

        // Redirect ke halaman reset password dengan token + email
        return redirect()->route('password.reset', ['token' => $token])
            ->with('email', $email);
    }

    /**
     * Kirim ulang OTP reset password.
     */
    public function resend(Request $request): RedirectResponse
    {
        $email = session('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $this->otpService->sendPasswordResetOtp($email);
        }

        return back()->with('status', 'otp-sent');
    }
}
