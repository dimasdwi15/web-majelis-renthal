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

        // Buat user baru
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),

            // DEFAULT ROLE USER
            'role'      => 'user',

            'google_id' => $request->firebase_uid,
        ]);

        // Generate Sanctum token
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

        // Cek email & password
        if (!Auth::attempt([
            'email'    => $request->email,
            'password' => $request->password
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau kata sandi salah.',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // Hapus token lama
        $user->tokens()->delete();

        // Buat token baru
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

            // Update data user Google
            $user->update([
                'google_id' => $request->google_id,
                'avatar'    => $request->avatar ?? $user->avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        } else {

            // Buat akun baru Google
            $user = User::create([
                'name'              => $request->name,
                'email'             => $request->email,
                'password'          => Hash::make(Str::random(32)),

                // DEFAULT ROLE USER
                'role'              => 'user',

                'google_id'         => $request->google_id,
                'avatar'            => $request->avatar,
                'email_verified_at' => now(),
            ]);
        }

        // Hapus token lama
        $user->tokens()->delete();

        // Buat token baru
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
            'email.exists'        => 'Email tidak ditemukan.',
            'password.confirmed'  => 'Konfirmasi password tidak cocok.',
            'password.min'        => 'Password minimal 6 karakter.',
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
}
