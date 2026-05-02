<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Tampilkan form register.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Validasi data, simpan ke session (BUKAN ke DB), kirim OTP, redirect ke halaman OTP.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone'    => ['nullable', 'string', 'max:20'],
            'alamat'   => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Simpan data pendaftaran ke session — akun BELUM dibuat di database
        session([
            'pending_registration' => [
                'name'     => $request->name,
                'email'    => $request->email,
                'phone'    => $request->phone,
                'alamat'   => $request->alamat,
                'password' => Hash::make($request->password),
            ]
        ]);

        // Kirim OTP ke email tanpa membuat user di DB
        $this->otpService->sendOtpToEmail($request->email);

        // Redirect ke halaman input OTP
        return redirect()->route('verification.notice')
            ->with('info', 'Kode OTP telah dikirim ke email Anda. Silakan periksa inbox atau folder spam.');
    }
}
