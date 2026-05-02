<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\Verified;
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
     */
    public function notice(Request $request)
    {
        // Jika user sudah login DAN sudah verified → langsung ke dashboard
        // (tidak perlu tampilkan popup sukses di halaman OTP lagi)
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
     *   → Jika valid, buat user di DB dengan email_verified_at = now()
     *   → Login, redirect langsung ke dashboard dengan pesan sukses
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

            // Buat user — email_verified_at langsung diisi sekarang
            // karena OTP sudah terbukti valid
            $user = User::create([
                'name'              => $pending['name'],
                'email'             => $pending['email'],
                'phone'             => $pending['phone'] ?? null,
                'alamat'            => $pending['alamat'] ?? null,
                'password'          => $pending['password'],
                'google_id'         => $pending['google_id'] ?? null,
                'avatar'            => $pending['avatar'] ?? null,
                'email_verified_at' => now(), // ← bisa diisi karena sudah ada di $fillable
            ]);

            // Bersihkan session pending
            session()->forget('pending_registration');

            // Login user
            Auth::login($user);

            // ── FIX #2: Redirect langsung ke dashboard, BUKAN ke verification.notice
            // Dulu redirect ke verification.notice menyebabkan loop:
            // middleware 'verified' menolak → redirect ke notice → user bingung
            return redirect()->route('user.dashboard')
                ->with('success', 'Selamat datang! Akun Anda berhasil dibuat dan email telah diverifikasi.');
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
     * Kirim ulang OTP.
     */
    public function resend(Request $request): RedirectResponse
    {
        $pending = session('pending_registration');

        // ── Pending registration ──────────────────────────────────────────────
        if ($pending) {
            $this->otpService->sendOtpToEmail($pending['email']);
            return back()->with('status', 'verification-link-sent');
        }

        // ── User sudah login ──────────────────────────────────────────────────
        $user = $request->user();

        if (! $user) {
            return redirect()->route('register');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('user.dashboard');
        }

        $this->otpService->sendOtp($user);

        return back()->with('status', 'verification-link-sent');
    }
}
