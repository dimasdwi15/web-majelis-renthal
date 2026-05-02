<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ForgotPasswordOtpController;

// ─── Guest routes ─────────────────────────────────────────────────────────────

Route::middleware('guest')->group(function () {

    Route::get('/register',  [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login',  [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password',  [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}',  [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password',         [NewPasswordController::class, 'store'])->name('password.store');
});

// ─── Email Verification — halaman OTP (tanpa wajib login) ────────────────────
//
// Sengaja TIDAK pakai middleware 'auth' agar user yang baru daftar
// (pending_registration di session, belum ada di DB) bisa mengakses halaman ini.
// Controller sendiri yang mengecek session vs user login.

Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
    ->name('verification.notice');

Route::post('/email/verify-otp', [EmailVerificationController::class, 'verifyOtp'])
    ->middleware('throttle:10,1')
    ->name('verification.otp');

Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:6,1')
    ->name('verification.send');

// ─── Routes yang butuh login ──────────────────────────────────────────────────

Route::middleware('auth')->group(function () {

    /**
     * Klik link verifikasi dari email (signed URL dari Laravel).
     * Hanya berlaku untuk user yang sudah ada di DB (bukan pending registration).
     */
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('user.dashboard')
                ->with('success', 'Email sudah terverifikasi sebelumnya.');
        }

        $request->fulfill();

        return redirect()->route('user.dashboard')
            ->with('success', 'Email Anda berhasil diverifikasi!');
    })
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

    // Konfirmasi password
    Route::get('/confirm-password',  [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// Lupa password — OTP flow
Route::get('/forgot-password',  [PasswordResetLinkController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

// ← TAMBAHKAN INI:
Route::get('/forgot-password/otp',         [ForgotPasswordOtpController::class, 'create'])->name('password.otp');
Route::post('/forgot-password/otp',        [ForgotPasswordOtpController::class, 'store'])->name('password.otp.verify');
Route::post('/forgot-password/otp/resend', [ForgotPasswordOtpController::class, 'resend'])
    ->middleware('throttle:6,1')
    ->name('password.otp.resend');
