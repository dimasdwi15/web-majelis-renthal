<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;
use App\Models\RecommendationHistory;
use App\Models\XpLog;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

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
        'fcm_token',
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

    public function recommendationHistories()
    {
        return $this->hasMany(RecommendationHistory::class);
    }

    // ─── Rewards ───────────────────────────────────────────

    public function reward()
    {
        return $this->hasOne(UserReward::class);
    }

    public function vouchers()
    {
        return $this->hasMany(UserVoucher::class);
    }

    public function xpLogs()
    {
        return $this->hasMany(XpLog::class);
    }

    /**
     * Ambil atau buat UserReward untuk user ini.
     */
    public function getOrCreateReward(): UserReward
    {
        return $this->reward()->firstOrCreate(
            ['user_id' => $this->id],
            [
                'total_xp'        => 0,
                'current_xp'      => 0,
                'level'           => 1,
                'available_boxes' => 0,
                'current_streak'  => 0,
            ]
        );
    }

    

}
