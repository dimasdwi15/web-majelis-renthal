<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Halaman notifikasi / form input OTP.
     *
     * Dapat diakses oleh:
     * 1. Guest yang punya session 'pending_registration' (baru daftar, belum ada di DB)
     * 2. User yang sudah login tapi belum terverifikasi
     */
    public function notice(Request $request)
    {
        // Izinkan halaman tampil untuk show popup sukses meski sudah verified
        if (session('registration_success')) {
            $email = $request->user()?->email ?? session('pending_registration.email', '');
            return view('auth.verify-email', compact('email'));
        }

        if ($request->user() && $request->user()->hasVerifiedEmail()) {
            return redirect()->route('user.dashboard');
        }

        $pending = session('pending_registration');

        if (! $pending && ! $request->user()) {
            return redirect()->route('register')
                ->with('error', 'Sesi pendaftaran tidak ditemukan. Silakan daftar ulang.');
        }

        $email = $pending['email'] ?? $request->user()?->email;

        return view('auth.verify-email', compact('email'));
    }

    /**
     * Proses verifikasi kode OTP yang dimasukkan user.
     *
     * FLOW 1 — Pending registration (user belum ada di DB):
     *   → Verifikasi OTP via email saja
     *   → Jika valid, buat user di DB, login, redirect ke dashboard
     *
     * FLOW 2 — User sudah ada di DB tapi belum verified:
     *   → Verifikasi OTP via User model
     *   → Jika valid, redirect ke dashboard
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $pending = session('pending_registration');

        // ── FLOW 1: Pending registration ─────────────────────────────────────
        if ($pending) {
            $email = $pending['email'];

            if (! $this->otpService->verifyOtpByEmail($email, $request->otp)) {
                return back()->withErrors([
                    'otp' => 'Kode OTP tidak valid atau sudah kadaluarsa. Silakan minta kode baru.'
                ]);
            }

            // Buat user di database — sertakan google_id & avatar jika ada (daftar via Google)
            $user = User::create([
                'name'              => $pending['name'],
                'email'             => $pending['email'],
                'phone'             => $pending['phone'] ?? null,
                'alamat'            => $pending['alamat'] ?? null,
                'password'          => $pending['password'],
                'google_id'         => $pending['google_id'] ?? null,  // ← tambahan
                'avatar'            => $pending['avatar'] ?? null,     // ← tambahan
                'email_verified_at' => now(),
            ]);

            session()->forget('pending_registration');

            Auth::login($user);

            return redirect()->route('verification.notice')
                ->with('registration_success', true);
        }

        // ── FLOW 2: User sudah ada di DB, belum verified ─────────────────────
        $user = $request->user();

        if (! $user) {
            return redirect()->route('register')
                ->with('error', 'Sesi tidak ditemukan. Silakan daftar atau login ulang.');
        }

        if (! $this->otpService->verifyOtp($user, $request->otp)) {
            return back()->withErrors([
                'otp' => 'Kode OTP tidak valid atau sudah kadaluarsa. Silakan minta kode baru.'
            ]);
        }

        return redirect()->route('user.dashboard')
            ->with('success', 'Email Anda berhasil diverifikasi!');
    }

    /**
     * Kirim ulang OTP (atau email verifikasi link untuk user yang sudah ada di DB).
     */
    public function resend(Request $request): RedirectResponse
    {
        $pending = session('pending_registration');

        // ── Pending registration: kirim ulang OTP ke email di session ─────────
        if ($pending) {
            $this->otpService->sendOtpToEmail($pending['email']);

            return back()->with('status', 'verification-link-sent');
        }

        // ── User sudah login: kirim ulang email verifikasi + OTP baru ─────────
        $user = $request->user();

        if (! $user) {
            return redirect()->route('register');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('user.dashboard');
        }

        // Kirim ulang link verifikasi Laravel + OTP
        $user->sendEmailVerificationNotification();
        $this->otpService->sendOtp($user);

        return back()->with('status', 'verification-link-sent');
    }
}
