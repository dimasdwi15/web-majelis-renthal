<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail; // <-- import interface
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;

/**
 * Implementasikan MustVerifyEmail agar Laravel tahu
 * bahwa model ini butuh verifikasi email.
 * Interface ini menyediakan kontrak: sendEmailVerificationNotification(),
 * hasVerifiedEmail(), dan markEmailAsVerified().
 */
class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'alamat',
        'google_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * Hanya admin & super_admin yang bisa akses Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    // ─── Relasi ────────────────────────────────────────────

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'user_id');
    }

    public function dendaDibuat()
    {
        return $this->hasMany(Denda::class, 'dibuat_oleh');
    }

    public function logAdmin()
    {
        return $this->hasMany(LogAdmin::class, 'user_id');
    }

    public function jaminanIdentitas()
    {
        return $this->hasMany(JaminanIdentitas::class, 'user_id');
    }

    public function emailOtps()
    {
        return $this->hasMany(EmailOtp::class, 'email', 'email');
    }
}
