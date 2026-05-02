<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected FirebaseAuth $firebaseAuth,
        protected OtpService $otpService,
    ) {}

    public function handleToken(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            $verifiedToken = $this->firebaseAuth->verifyIdToken($request->id_token);
            $claims        = $verifiedToken->claims();

            $googleId = $claims->get('sub');
            $email    = $claims->get('email');
            $name     = $claims->get('name');
            $avatar   = $claims->get('picture');

            // ── 1. Cari user by google_id ─────────────────────────────────────
            $user = User::where('google_id', $googleId)->first();

            // ── 2. Jika tidak ada, cari by email ─────────────────────────────
            if (! $user) {
                $user = User::where('email', $email)->first();
            }

            // ── 3. User sudah ada di database ────────────────────────────────
            if ($user) {
                // Update google_id & avatar jika belum ada
                $user->update([
                    'google_id' => $googleId,
                    'avatar'    => $avatar,
                ]);

                // Jika email sudah terverifikasi → langsung login, tidak perlu OTP
                if ($user->hasVerifiedEmail()) {
                    Auth::login($user, remember: true);

                    return response()->json([
                        'success'  => true,
                        'redirect' => $this->redirectAfterLogin($user),
                    ]);
                }

                // Email belum terverifikasi → kirim OTP, arahkan ke halaman verifikasi
                $this->otpService->sendOtpToEmail($email);

                // Simpan flag di session agar EmailVerificationController tahu
                // ini bukan pending_registration (user sudah ada di DB)
                Auth::login($user, remember: true);

                return response()->json([
                    'success'  => true,
                    'redirect' => route('verification.notice'),
                ]);
            }

            // ── 4. User baru — belum ada di database sama sekali ─────────────
            //    Simpan ke session (pending_registration), kirim OTP.
            //    Akun baru dibuat di DB hanya setelah OTP berhasil diverifikasi.
            session([
                'pending_registration' => [
                    'name'      => $name,
                    'email'     => $email,
                    'phone'     => null,
                    'alamat'    => null,
                    'password'  => Hash::make(Str::random(32)), // random — login via Google
                    'google_id' => $googleId,
                    'avatar'    => $avatar,
                ],
            ]);

            $this->otpService->sendOtpToEmail($email);

            return response()->json([
                'success'  => true,
                'redirect' => route('verification.notice'),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Autentikasi Google gagal: ' . $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Tentukan redirect setelah login berhasil berdasarkan role.
     */
    private function redirectAfterLogin(User $user): string
    {
        return in_array($user->role, ['super_admin', 'admin'])
            ? '/admin'
            : route('user.dashboard');
    }
}
