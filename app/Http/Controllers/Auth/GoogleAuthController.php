<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected FirebaseAuth $firebaseAuth,
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
                    'google_id'     => $googleId,
                    'avatar'        => $avatar,
                    'auth_provider' => $user->auth_provider === 'local' ? 'hybrid' : $user->auth_provider,
                ]);

                // Jika email sudah terverifikasi → langsung login, tidak perlu OTP
                if ($user->hasVerifiedEmail()) {
                    Auth::login($user, remember: true);

                    return response()->json([
                        'success'  => true,
                        'redirect' => $this->redirectAfterLogin($user),
                    ]);
                }
                $user->markEmailAsVerified();
                Auth::login($user, remember: true);

                return response()->json([
                    'success'  => true,
                    'redirect' => $this->redirectAfterLogin($user),
                ]);
            }

            // Buat akun langsung — email Google sudah terverifikasi, tidak perlu OTP
            $user = User::create([
                'name'              => $name,
                'email'             => $email,
                'google_id'         => $googleId,
                'avatar'            => $avatar,
                'auth_provider'     => 'google',
                'email_verified_at' => now(),
                'password'          => null,
            ]);

            Auth::login($user, remember: true);

            return response()->json([
                'success'  => true,
                'redirect' => $this->redirectAfterLogin($user),
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
