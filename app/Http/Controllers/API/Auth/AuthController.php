<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // REGISTER — Email & Password
    // POST /api/auth/register
    // ─────────────────────────────────────────────────────────────
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:6|confirmed',
            'firebase_uid' => 'nullable|string',
        ], [
            'email.unique'       => 'Email sudah digunakan.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min'       => 'Kata sandi minimal 6 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => 'user',
            'google_id' => $request->firebase_uid,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil.',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────
    // LOGIN — Email & Password
    // POST /api/auth/login
    //
    // Jika akun terdaftar via Google (google_id ada) dan password
    // tidak cocok → kembalikan auth_provider: 'google' supaya
    // Flutter bisa tampilkan pesan yang tepat.
    // ─────────────────────────────────────────────────────────────
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
        $credentials = [
            'email'    => $request->email,
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials)) {
            // Cek apakah user ini sebenarnya terdaftar via Google
            $existingUser = User::where('email', $request->email)->first();

            if ($existingUser && $existingUser->google_id) {
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

        // Hapus token lama & buat token baru
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // GOOGLE AUTH
    // POST /api/auth/google
    //
    // Jika email sudah terdaftar via email (tidak punya google_id)
    // → kembalikan auth_provider: 'email' supaya Flutter bisa
    // tampilkan pesan yang tepat.
    // ─────────────────────────────────────────────────────────────
    public function googleAuth(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'google_id' => 'required|string',
            'name'      => 'required|string|max:255',
            'email'     => 'required|email',
            'avatar'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Cari user berdasarkan google_id atau email
        $user = User::where('google_id', $request->google_id)
            ->orWhere('email', $request->email)
            ->first();

        if ($user) {
            // Jika akun ditemukan via email tapi TIDAK punya google_id
            // berarti akun ini terdaftar dengan email & password
            if (!$user->google_id) {
                return response()->json([
                    'success'       => false,
                    'message'       => 'Akun ini terdaftar menggunakan email & password. Silakan login dengan email.',
                    'auth_provider' => 'email',
                ], 403);
            }

            // Update data user Google yang sudah ada
            $user->update([
                'google_id'         => $request->google_id,
                'avatar'            => $request->avatar ?? $user->avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        } else {
            // Buat akun baru via Google
            $user = User::create([
                'name'              => $request->name,
                'email'             => $request->email,
                'password'          => Hash::make(Str::random(32)),
                'role'              => 'user',
                'google_id'         => $request->google_id,
                'avatar'            => $request->avatar,
                'email_verified_at' => now(),
            ]);
        }

        // Hapus token lama & buat token baru
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Google berhasil.',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // LOGOUT
    // ─────────────────────────────────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // ME
    // ─────────────────────────────────────────────────────────────
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user'    => $this->formatUser($request->user()),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // FORMAT USER
    // ─────────────────────────────────────────────────────────────
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
            'google_id'         => $user->google_id,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // SET PASSWORD GOOGLE ACCOUNT
    // POST /api/auth/set-password
    // ─────────────────────────────────────────────────────────────
    public function setPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'email.exists'       => 'Email tidak ditemukan.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min'       => 'Password minimal 6 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil dibuat.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // UPDATE FCM TOKEN
    // POST /api/auth/fcm-token
    // ─────────────────────────────────────────────────────────────
    public function updateFcmToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'FCM Token wajib diisi.',
            ], 422);
        }

        $user = $request->user();
        $user->update(['fcm_token' => $request->fcm_token]);

        return response()->json([
            'success' => true,
            'message' => 'FCM Token berhasil diperbarui.',
        ]);
    }
}
