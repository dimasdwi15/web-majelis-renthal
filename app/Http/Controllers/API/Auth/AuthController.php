<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpVerificationMail;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly FirebaseService $firebase
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRASI LANGKAH 1 — Kirim OTP
    // POST /api/auth/send-register-otp
    // ─────────────────────────────────────────────────────────────────────────
    public function sendRegisterOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Email sudah terdaftar. Silakan login.',
            ], 422);
        }

        EmailOtp::where('email', $request->email)->delete();
        $otp = EmailOtp::createForEmail($request->email);
        Mail::to($request->email)->send(new OtpVerificationMail($otp));

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP telah dikirim ke email Anda.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRASI LANGKAH 2 — Verifikasi OTP + Buat User
    // POST /api/auth/register
    // ─────────────────────────────────────────────────────────────────────────
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'otp'      => 'required|string|size:6',
        ], [
            'email.unique'       => 'Email sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min'       => 'Kata sandi minimal 6 karakter.',
            'otp.required'       => 'Kode OTP wajib diisi.',
            'otp.size'           => 'Kode OTP harus 6 digit.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $otpRecord = EmailOtp::where('email', $request->email)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP tidak ditemukan atau sudah kedaluwarsa.',
            ], 422);
        }

        if (! Hash::check($request->otp, $otpRecord->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP salah.',
            ], 422);
        }

        $otpRecord->update(['used' => true]);

        // ── Buat user dengan provider 'local' ─────────────────────────────
        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'role'              => 'user',
            'auth_provider'     => 'local',   // ← selalu local untuk email register
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil.',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN — Email & Password
    // POST /api/auth/login
    // ─────────────────────────────────────────────────────────────────────────
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Coba autentikasi
        if (! Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $existingUser = User::where('email', $request->email)->first();

            // Akun Google murni — tidak punya password lokal
            if ($existingUser && $existingUser->auth_provider === 'google') {
                return response()->json([
                    'success'       => false,
                    'message'       => 'Akun ini terdaftar menggunakan Google. Silakan login dengan Google.',
                    'auth_provider' => 'google',
                ], 401);
            }

            return response()->json([
                'success' => false,
                'message' => 'Email atau kata sandi salah.',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GOOGLE AUTH — Verifikasi Firebase ID Token (server-side!)
    // POST /api/auth/google
    //
    // PERUBAHAN PENTING dari versi lama:
    //   - Sebelum: menerima google_id langsung dari client (TIDAK AMAN)
    //   - Sekarang: menerima firebase_token, diverifikasi di server
    //   - Tidak lagi menyimpan password random untuk akun Google
    // ─────────────────────────────────────────────────────────────────────────
    public function googleAuth(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string',  // ← ganti dari google_id
        ], [
            'firebase_token.required' => 'Firebase token wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // ── VERIFIKASI Firebase ID Token di server ─────────────────────────
        try {
            $firebaseUser = $this->firebase->verifyIdToken($request->firebase_token);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token Google tidak valid: ' . $e->getMessage(),
            ], 401);
        }

        $uid   = $firebaseUser['uid'];
        $email = $firebaseUser['email'];
        $name  = $firebaseUser['name']    ?? '';
        $avatar = $firebaseUser['picture'] ?? null;

        if (! $email) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan di akun Google ini.',
            ], 422);
        }

        // ── Cari user berdasarkan google_id atau email ─────────────────────
        $user = User::where('google_id', $uid)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            // Akun email/password yang belum pernah pakai Google
            if ($user->auth_provider === 'local') {
                return response()->json([
                    'success'       => false,
                    'message'       => 'Akun ini terdaftar menggunakan email & password. Silakan login dengan email.',
                    'auth_provider' => 'email',
                ], 403);
            }

            // Update data Google (nama, avatar bisa berubah)
            $user->update([
                'google_id'         => $uid,
                'avatar'            => $avatar ?? $user->avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        } else {
            // ── Buat akun baru via Google ─────────────────────────────────
            // TIDAK ADA password random — password null untuk Google murni
            $user = User::create([
                'name'              => $name ?: explode('@', $email)[0],
                'email'             => $email,
                'password'          => null,   // ← tidak ada random password
                'role'              => 'user',
                'google_id'         => $uid,
                'auth_provider'     => 'google',
                'avatar'            => $avatar,
                'email_verified_at' => now(),
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Google berhasil.',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SET PASSWORD — untuk akun Google yang ingin tambah password lokal
    // POST /api/auth/set-password
    //
    // Setelah berhasil, auth_provider diupdate ke 'hybrid'
    // ─────────────────────────────────────────────────────────────────────────
    public function setPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.min'       => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        /** @var User $user */
        $user = $request->user();

        // Hanya akun Google yang boleh set password lewat endpoint ini
        if (! in_array($user->auth_provider, ['google', 'hybrid'])) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint ini hanya untuk akun Google.',
            ], 403);
        }

        $user->update([
            'password'      => Hash::make($request->password),
            'auth_provider' => 'hybrid',  // ← sekarang bisa login dua cara
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil dibuat. Akun Anda sekarang bisa login dengan Google maupun email.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // LINK GOOGLE — untuk akun local yang ingin hubungkan Google
    // POST /api/auth/link-google  (protected)
    // ─────────────────────────────────────────────────────────────────────────────
    public function linkGoogle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $firebaseUser = $this->firebase->verifyIdToken($request->firebase_token);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Token Google tidak valid.'], 401);
        }

        $uid = $firebaseUser['uid'];

        // Cek google_id sudah dipakai akun lain
        if (User::where('google_id', $uid)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Google ini sudah terhubung ke akun lain.',
            ], 422);
        }

        /** @var User $user */
        $user = $request->user();
        $user->update([
            'google_id'     => $uid,
            'auth_provider' => 'hybrid',
            'avatar'        => $user->avatar ?? $firebaseUser['picture'],
        ]);

        return response()->json([
            'success'       => true,
            'message'       => 'Akun Google berhasil dihubungkan.',
            'auth_provider' => 'hybrid',
            'user'          => $this->formatUser($user->fresh()),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOGOUT
    // ─────────────────────────────────────────────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ME
    // ─────────────────────────────────────────────────────────────────────────
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user'    => $this->formatUser($request->user()),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UPDATE FCM TOKEN
    // ─────────────────────────────────────────────────────────────────────────
    public function updateFcmToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'FCM Token wajib diisi.'], 422);
        }

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['success' => true, 'message' => 'FCM Token berhasil diperbarui.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FORMAT USER — response JSON yang konsisten
    // ─────────────────────────────────────────────────────────────────────────
    private function formatUser(User $user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'role'              => $user->role,
            'phone'             => $user->phone,
            'alamat'            => $user->alamat,
            'avatar'            => $user->avatar,
            'email_verified_at' => $user->email_verified_at,
            'auth_provider'     => $user->auth_provider,   // ← BARU: dikirim ke Flutter
            'google_id'         => $user->google_id,
            'has_password'      => ! is_null($user->password),  // ← helper untuk Flutter
        ];
    }
}
