<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Tampilkan form input email lupa password.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Proses email yang dimasukkan user.
     * Kirim OTP ke email, simpan email di session, redirect ke halaman OTP.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        // Cari user — jangan beri tahu apakah email terdaftar atau tidak
        // untuk mencegah user enumeration attack
        $user = User::where('email', $request->email)->first();

        if ($user) {
            $this->otpService->sendOtpToEmail($user->email);
        }

        // Simpan email di session untuk dipakai di halaman OTP
        session(['password_reset_email' => $request->email]);

        return redirect()->route('password.otp')
            ->with('status', 'otp-sent');
    }
}
