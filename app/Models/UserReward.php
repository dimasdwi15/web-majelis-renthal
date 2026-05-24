<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class UserReward extends Model
{
    protected $fillable = [
        'user_id',
        'total_xp',
        'current_xp',
        'level',
        'available_boxes',
        'current_streak',
        'last_checkin_date',
        'is_daily_claimed',
    ];

    protected $casts = [
        'last_checkin_date' => 'date',
        'is_daily_claimed'  => 'boolean',
    ];

    // ── XP per level ─────────────────────────────────────────────────────
    public const XP_PER_LEVEL = 500;

    // ── Nama level ───────────────────────────────────────────────────────
    public static function getLevelName(int $level): string
    {
        return match (true) {
            $level <= 1 => 'Trail Starter',
            $level == 2 => 'Nature Wanderer',
            $level == 3 => 'Trail Explorer',
            $level == 4 => 'Mountain Climber',
            default     => 'Summit Master',
        };
    }

    public function getLevelNameAttribute(): string
    {
        return self::getLevelName($this->level);
    }

    // ── Streak XP per hari (hari 1–7) ────────────────────────────────────
    public static function getStreakXpValues(): array
    {
        return [10, 10, 10, 10, 20, 10, 50]; // index 0–6 = hari ke-1 s/d 7
    }

    // ── Apakah sudah checkin hari ini? (cek berdasarkan tanggal, bukan flag) ─
    public function isDailyClaimedToday(): bool
    {
        if (! $this->last_checkin_date) return false;
        return $this->last_checkin_date->isToday();
    }

    // ── Auto-reset streak jika skip > 1 hari ─────────────────────────────
    public function autoResetStreakIfNeeded(): bool // ← tambah return bool
    {
        if (! $this->last_checkin_date) return false;

        $yesterday   = Carbon::yesterday()->startOfDay();
        $lastCheckin = $this->last_checkin_date->copy()->startOfDay();

        if ($lastCheckin->lt($yesterday)) {
            $this->current_streak = 0;
            return true; // ← ada perubahan
        }

        return false; // ← tidak ada perubahan
    }

    // ── Relations ────────────────────────────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function xpLogs(): HasMany
    {
        return $this->hasMany(XpLog::class, 'user_id', 'user_id');
    }
}
