<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // GET /api/profile
    // Mengembalikan data profil user yang sedang login
    // ─────────────────────────────────────────────────────────────────
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $this->formatUser($user),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // PUT /api/profile
    // Update nama, phone, alamat (email tidak bisa diubah)
    // ─────────────────────────────────────────────────────────────────
    public function update(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
        ]);

        $user = $request->user();
        $user->update([
            'name'   => $request->name,
            'phone'  => $request->phone,
            'alamat' => $request->alamat,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $this->formatUser($user->fresh()),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /api/profile/change-password
    // Body: current_password, new_password, new_password_confirmation
    // ─────────────────────────────────────────────────────────────────
    public function changePassword(Request $request)
    {
        $user     = $request->user();
        $provider = $user->auth_provider ?? 'local';

        if ($provider === 'google') {
            // Akun Google murni: tidak butuh password lama
            $request->validate([
                'new_password' => 'required|string|min:8|confirmed',
            ], [
                'new_password.min'       => 'Kata sandi baru minimal 8 karakter.',
                'new_password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            ]);

            $user->update([
                'password'      => Hash::make($request->new_password),
                'auth_provider' => 'hybrid',
            ]);

            return response()->json([
                'success'       => true,
                'message'       => 'Password berhasil dibuat. Akun Anda sekarang bisa login dengan Google maupun email.',
                'auth_provider' => 'hybrid',
            ]);
        }

        // Akun local / hybrid: wajib verifikasi password lama
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'new_password.min'          => 'Kata sandi baru minimal 8 karakter.',
            'new_password.confirmed'    => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        if (is_null($user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini tidak memiliki password lokal.',
            ], 422);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi saat ini tidak sesuai.',
            ], 422);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi baru tidak boleh sama dengan yang lama.',
            ], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diperbarui.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helper: format data user yang dikembalikan ke Flutter
    // ─────────────────────────────────────────────────────────────────
    private function formatUser($user): array
    {
        return [
            'id'                 => $user->id,
            'name'               => $user->name,
            'email'              => $user->email,
            'phone'              => $user->phone,
            'alamat'             => $user->alamat,
            'avatar'             => $user->avatar,
            'google_id'          => $user->google_id,
            'auth_provider'      => $user->auth_provider ?? 'local',
            'email_verified_at'  => $user->email_verified_at,
            'has_password'       => ! is_null($user->password),
            'role'               => $user->role,
        ];
    }
}
