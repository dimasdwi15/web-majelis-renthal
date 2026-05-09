<?php

namespace App\Http\Controllers\API;

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
        $request->validate([
            'current_password'          => 'required|string',
            'new_password'              => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Akun Google tidak punya password lokal
        if (is_null($user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda terdaftar via Google dan tidak menggunakan kata sandi manual.',
            ], 422);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi saat ini tidak sesuai.',
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
            'email_verified_at'  => $user->email_verified_at,
            'has_password'       => ! is_null($user->password),
            'role'               => $user->role,
        ];
    }
}
