<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;

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
        'email_verified_at', 
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
