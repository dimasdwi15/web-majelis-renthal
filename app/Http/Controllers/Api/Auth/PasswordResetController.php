<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/forgot-password
    // Body : { email }
    // Kirim OTP ke email yang terdaftar.
    // ─────────────────────────────────────────────────────────────────────────
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'Email tidak terdaftar di sistem kami.',
        ]);

        // Buat OTP baru (hapus OTP lama untuk email ini secara otomatis di model)
        $otp = EmailOtp::createForEmail($request->email);

        // Kirim email
        Mail::to($request->email)->send(
            new PasswordResetOtpMail($otp, $otp->plain_otp)
        );

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP telah dikirim ke email Anda. Berlaku selama 10 menit.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/verify-reset-otp
    // Body : { email, otp }
    // Verifikasi OTP, kembalikan reset_token yang berlaku 15 menit.
    // ─────────────────────────────────────────────────────────────────────────
    public function verifyResetOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp'   => ['required', 'string', 'size:6'],
        ]);

        /** @var EmailOtp|null $emailOtp */
        $emailOtp = EmailOtp::where('email', $request->email)
            ->where('used', false)
            ->latest()
            ->first();

        // Gunakan isValid() + verifyPlain() sesuai model yang ada
        if (! $emailOtp || ! $emailOtp->isValid() || ! $emailOtp->verifyPlain($request->otp)) {
            throw ValidationException::withMessages([
                'otp' => ['Kode OTP tidak valid atau sudah kedaluwarsa.'],
            ]);
        }

        // Buat reset_token (encrypted, berisi email + expiry 15 menit)
        // Sengaja TIDAK markAsUsed() di sini — OTP ditandai used setelah
        // password benar-benar berhasil diperbarui di resetPassword()
        $resetToken = encrypt(json_encode([
            'email'      => $request->email,
            'expires_at' => now()->addMinutes(15)->timestamp,
        ]));

        return response()->json([
            'success'     => true,
            'message'     => 'OTP valid. Silakan buat password baru Anda.',
            'reset_token' => $resetToken,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/auth/reset-password
    // Body : { reset_token, password, password_confirmation }
    // Reset password user menggunakan token dari verifyResetOtp.
    // ─────────────────────────────────────────────────────────────────────────
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'reset_token'           => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ], [
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        // Dekripsi token
        try {
            $payload = json_decode(decrypt($request->reset_token), true);
        } catch (\Exception) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah dimanipulasi.',
            ], 422);
        }

        // Validasi struktur payload
        if (! isset($payload['email'], $payload['expires_at'])) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid.',
            ], 422);
        }

        // Cek kedaluwarsa token (15 menit)
        if (now()->timestamp > $payload['expires_at']) {
            return response()->json([
                'success' => false,
                'message' => 'Token sudah kedaluwarsa. Silakan ulangi proses reset password.',
            ], 422);
        }

        // Cari user
        $user = User::where('email', $payload['email'])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak ditemukan.',
            ], 404);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Tandai OTP sebagai sudah dipakai menggunakan markAsUsed()
        $latestOtp = EmailOtp::where('email', $payload['email'])
            ->where('used', false)
            ->latest()
            ->first();

        $latestOtp?->markAsUsed();

        // Cabut semua token Sanctum (paksa login ulang di semua device)
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui. Silakan login dengan password baru Anda.',
        ]);
    }
}
