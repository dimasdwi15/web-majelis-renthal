<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserReward;
use App\Models\UserVoucher;
use App\Models\VoucherTemplate;
use App\Models\MysteryBoxItem;
use App\Models\XpLog;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardsService
{
    // ── KONSTANTA ─────────────────────────────────────────────────────────
    public const XP_PER_LEVEL      = 500;
    public const XP_PER_RUPIAH     = 1000; // Rp1.000 = 1 XP  → Rp50.000 = 50 XP

    // ── HITUNG XP DARI TOTAL SEWA ─────────────────────────────────────────
    public static function hitungXpDariSewa(float $totalSewa): int
    {
        return (int) floor($totalSewa / self::XP_PER_RUPIAH);
    }

    // ── AWARD XP (earn) ───────────────────────────────────────────────────
    /**
     * Tambahkan XP ke user. Otomatis cek & proses level-up.
     *
     * @return array{leveled_up: bool, new_level: int, boxes_gained: int}
     */
    public function awardXp(User $user, int $amount, string $source, string $description, ?int $transaksiId = null): array
    {
        $reward = $user->getOrCreateReward();

        DB::transaction(function () use ($user, $reward, $amount, $source, $description, $transaksiId) {
            $reward->total_xp   += $amount;
            $reward->current_xp += $amount;
            $reward->save();

            XpLog::create([
                'user_id'      => $user->id,
                'amount'       => $amount,
                'source'       => $source,
                'description'  => $description,
                'transaksi_id' => $transaksiId,
            ]);
        });

        $reward->refresh();

        // Cek level up
        return $this->processLevelUp($user, $reward);
    }

    // ── SPEND XP (redeem voucher) ─────────────────────────────────────────
    public function spendXp(User $user, int $amount, string $description): bool
    {
        $reward = $user->getOrCreateReward();

        if ($reward->current_xp < $amount) return false;

        DB::transaction(function () use ($user, $reward, $amount, $description) {
            $reward->current_xp -= $amount;
            $reward->total_xp   -= $amount;
            $reward->save();

            XpLog::create([
                'user_id'     => $user->id,
                'amount'      => -$amount,
                'source'      => 'redeem',
                'description' => $description,
            ]);
        });

        return true;
    }

    // ── PROCESS LEVEL-UP ──────────────────────────────────────────────────
    /**
     * XP TIDAK di-reset antar level — poin tetap cumulative.
     * Level up terjadi tiap user mendapatkan total 500 XP dari awal game,
     * dihitung dengan: level = floor(total_xp / 500) + 1
     */
    private function processLevelUp(User $user, UserReward $reward): array
    {
        $boxesGained = 0;
        $leveledUp   = false;

        $levelsGained = (int) floor($reward->current_xp / self::XP_PER_LEVEL);

        if ($levelsGained > 0) {
            $newLevel    = $reward->level + $levelsGained;
            $boxesGained = $levelsGained;
            $leveledUp   = true;

            $reward->update([
                'level'           => $newLevel,
                'current_xp'      => $reward->current_xp - ($levelsGained * self::XP_PER_LEVEL),
                'available_boxes' => $reward->available_boxes + $boxesGained,
            ]);

            // Log level up
            XpLog::create([
                'user_id'     => $user->id,
                'amount'      => - ($levelsGained * self::XP_PER_LEVEL),
                'source'      => 'level_up',
                'description' => "Naik ke Level {$newLevel}! +{$boxesGained} Mystery Box",
            ]);
        }

        return [
            'leveled_up'   => $leveledUp,
            'new_level'    => $reward->level,
            'boxes_gained' => $boxesGained,
        ];
    }

    // ── AWARD XP DARI CHECKOUT ────────────────────────────────────────────
    /**
     * Dipanggil saat:
     * - Midtrans: checkout sukses (langsung)
     * - COD: setelah admin ubah status ke "berjalan" (bayarCod)
     */
    public function awardCheckoutXp(Transaksi $transaksi): array
    {
        // Cegah double award
        if ($transaksi->xp_rewarded) {
            return ['leveled_up' => false, 'new_level' => 0, 'boxes_gained' => 0];
        }

        $xpAmount = self::hitungXpDariSewa((float) $transaksi->total_sewa);

        if ($xpAmount <= 0) {
            $transaksi->update(['xp_rewarded' => true]);
            return ['leveled_up' => false, 'new_level' => 0, 'boxes_gained' => 0];
        }

        $result = $this->awardXp(
            user: $transaksi->user,
            amount: $xpAmount,
            source: 'checkout',
            description: "Sewa #{$transaksi->nomor_transaksi} (Rp " . number_format($transaksi->total_sewa, 0, ',', '.') . ")",
            transaksiId: $transaksi->id,
        );

        $transaksi->update(['xp_rewarded' => true]);

        return $result;
    }

    // ── DAILY CHECK-IN ────────────────────────────────────────────────────
    /**
     * @return array{success: bool, xp_earned: int, streak: int, leveled_up: bool}
     */
    public function claimDaily(User $user): array
    {
        $reward = $user->getOrCreateReward();

        // Sudah claim hari ini?
        if ($reward->isDailyClaimedToday()) {
            return ['success' => false, 'xp_earned' => 0, 'streak' => $reward->current_streak, 'leveled_up' => false];
        }

        // Auto reset streak jika skip lebih dari 1 hari
        $reward->autoResetStreakIfNeeded();

        $streakValues = UserReward::getStreakXpValues();
        $xpEarned     = $streakValues[$reward->current_streak];

        $newStreak = ($reward->current_streak + 1) % 7; // setelah hari ke-7 reset ke 0

        DB::transaction(function () use ($user, $reward, $xpEarned, $newStreak) {
            $reward->total_xp         += $xpEarned;
            $reward->current_xp       += $xpEarned;
            $reward->current_streak    = $newStreak;
            $reward->last_checkin_date = today();
            $reward->is_daily_claimed  = true;
            $reward->save();

            XpLog::create([
                'user_id'     => $user->id,
                'amount'      => $xpEarned,
                'source'      => 'daily_checkin',
                'description' => 'Daily Check-in Hari ke-' . ($newStreak === 0 ? 7 : $newStreak),
            ]);
        });

        $reward->refresh();
        $levelResult = $this->processLevelUp($user, $reward);

        return [
            'success'    => true,
            'xp_earned'  => $xpEarned,
            'streak'     => $newStreak,
            'leveled_up' => $levelResult['leveled_up'],
        ];
    }

    // ── REDEEM VOUCHER (XP → Voucher) ────────────────────────────────────
    /**
     * @return array{success: bool, voucher: UserVoucher|null, message: string}
     */
    public function redeemVoucher(User $user, int $templateId): array
    {
        $template = VoucherTemplate::find($templateId);

        if (! $template || ! $template->is_active || is_null($template->xp_cost)) {
            return ['success' => false, 'voucher' => null, 'message' => 'Voucher tidak tersedia.'];
        }

        $reward = $user->getOrCreateReward();

        if ($reward->current_xp < $template->xp_cost) {
            return ['success' => false, 'voucher' => null, 'message' => 'XP tidak cukup.'];
        }

        $voucher = null;

        DB::transaction(function () use ($user, $reward, $template, &$voucher) {
            $spent = $this->spendXp($user, $template->xp_cost, "Tukar XP → Voucher {$template->code}");

            if (! $spent) {
                throw new \Exception('XP tidak cukup.');
            }

            $voucher = UserVoucher::create([
                'user_id'             => $user->id,
                'voucher_template_id' => $template->id,
                'unique_code'         => strtoupper($template->code . '-' . Str::random(6)),
                'expires_at'          => now()->addDays($template->valid_days),
            ]);
        });

        return ['success' => true, 'voucher' => $voucher->load('template'), 'message' => 'Voucher berhasil ditukar!'];
    }

    // ── BUKA MYSTERY BOX ─────────────────────────────────────────────────
    /**
     * Buka mystery box menggunakan item dari tabel mystery_box_items.
     * Item dipilih secara weighted random (bukan dari voucher_templates).
     * Data snapshot disimpan permanen di user_vouchers.
     *
     * @return array{success: bool, voucher: UserVoucher|null, message: string}
     */
    public function openMysteryBox(User $user): array
    {
        $reward = $user->getOrCreateReward();

        if ($reward->available_boxes <= 0) {
            return ['success' => false, 'voucher' => null, 'message' => 'Tidak ada mystery box tersedia.'];
        }

        // ── Ambil pool dari tabel mystery_box_items (bukan voucher_templates) ──
        $pool = MysteryBoxItem::where('is_active', true)->get();

        if ($pool->isEmpty()) {
            return ['success' => false, 'voucher' => null, 'message' => 'Mystery box sedang kosong. Hubungi admin.'];
        }

        // ── Pilih item secara weighted random ─────────────────────────────────
        $item    = MysteryBoxItem::weightedRandom($pool);
        $voucher = null;

        DB::transaction(function () use ($user, $reward, $item, &$voucher) {
            $reward->available_boxes -= 1;
            $reward->save();

            // Bangun kode unik untuk voucher ini
            $prefix     = strtoupper(Str::slug($item->title, ''));
            $uniqueCode = strtoupper(substr($prefix, 0, 8) . '-' . Str::random(6));

            // Snapshot foto URL (ambil dari relasi jika snapshot kosong)
            $fotoUrl = $item->barang_foto_url_snapshot;
            if (! $fotoUrl && $item->barang) {
                $foto = $item->barang->fotoUtama;
                if ($foto) {
                    $fotoUrl = $foto->path_foto;
                }
            }

            // Snapshot judul
            $titleSnapshot = $item->title;
            if ($item->type === 'free_rental') {
                $titleSnapshot = 'Gratis Sewa ' . $item->barang_nama;
            }

            // Buat UserVoucher dengan data snapshot mystery box
            // CATATAN: voucher_template_id = null karena ini bukan dari voucher_templates
            // Gunakan voucher_template_id dari dummy template jika diperlukan oleh constraint
            // Kita set nullable di kolom ini melalui migration terpisah jika perlu,
            // untuk sekarang kita gunakan pendekatan: cari atau buat template "MYSTERY_BOX_PLACEHOLDER"
            // Lebih sederhana: gunakan unique_code saja, template bisa null jika kita relax constraint.
            // Karena foreign key voucher_template_id NOT NULL, kita ambil dummy template atau
            // gunakan is_mystery_pool template yang ada.
            // SOLUSI TERBAIK: biarkan voucher_template_id nullable (lihat migration berikutnya)
            // Untuk sekarang, kita tidak perlu voucher_template_id untuk mystery box.
            // Kita update migration untuk membuatnya nullable.

            $voucher = UserVoucher::create([
                'user_id'                   => $user->id,
                'voucher_template_id'       => null, // mystery box tidak perlu template
                'mystery_box_item_id'       => $item->id,
                'mystery_title_snapshot'    => $titleSnapshot,
                'mystery_foto_url_snapshot' => $fotoUrl,
                'unique_code'               => $uniqueCode,
                'expires_at'                => now()->addDays($item->valid_days),
            ]);

            XpLog::create([
                'user_id'     => $user->id,
                'amount'      => 0,
                'source'      => 'mystery_box',
                'description' => "Membuka Mystery Box: Mendapatkan {$titleSnapshot}!",
            ]);
        });

        return [
            'success' => true,
            'voucher' => $voucher->load(['mysteryBoxItem.barang']),
            'message' => "Mendapatkan {$voucher->mystery_title_snapshot}!",
        ];
    }

    // ── APPLY VOUCHER di CHECKOUT ─────────────────────────────────────────
    /**
     * Validasi & mark voucher sebagai digunakan.
     * @return array{valid: bool, message: string, discount: int, is_free_item: bool, free_barang_id: int|null}
     */
    public function applyVoucher(User $user, string $uniqueCode, float $checkoutAmount): array
    {
        $voucher = UserVoucher::with([
            'template.freeBarang',
            'mysteryBoxItem.barang',  // ← tambah eager load mystery box
        ])
            ->where('user_id', $user->id)
            ->where('unique_code', $uniqueCode)
            ->active()
            ->first();

        if (! $voucher) {
            return [
                'valid'          => false,
                'message'        => 'Voucher tidak ditemukan atau sudah kadaluwarsa.',
                'discount'       => 0,
                'is_free_item'   => false,
                'free_barang_id' => null,
            ];
        }

        // ── CASE 1: Voucher dari Mystery Box (template = null) ────────────────
        if (is_null($voucher->voucher_template_id)) {
            $item = $voucher->mysteryBoxItem;

            if (! $item) {
                return [
                    'valid'          => false,
                    'message'        => 'Data voucher mystery box tidak ditemukan.',
                    'discount'       => 0,
                    'is_free_item'   => false,
                    'free_barang_id' => null,
                ];
            }

            // Validasi minimum checkout untuk mystery box discount
            if ($item->type === 'discount' && $checkoutAmount < $item->min_checkout) {
                return [
                    'valid'          => false,
                    'message'        => 'Total sewa belum mencapai minimum voucher (Rp '
                        . number_format($item->min_checkout, 0, ',', '.') . ').',
                    'discount'       => 0,
                    'is_free_item'   => false,
                    'free_barang_id' => null,
                ];
            }

            $freeBarangId = null;
            if ($item->type === 'free_rental') {
                // Cek barang_id dari relasi atau dari UserVoucher.free_barang_id
                $freeBarangId = $item->barang_id ?? $voucher->free_barang_id;
            }

            return [
                'valid'          => true,
                'message'        => 'Voucher mystery box valid.',
                'discount'       => $item->type === 'discount' ? $item->discount_amount : 0,
                'is_free_item'   => $item->type === 'free_rental',
                'free_barang_id' => $freeBarangId,
                'voucher'        => $voucher,
            ];
        }

        // ── CASE 2: Voucher dari Template (XP redeem / normal) ────────────────
        $template = $voucher->template;

        // Defensive: template terhapus dari DB tapi voucher masih ada
        if (! $template) {
            return [
                'valid'          => false,
                'message'        => 'Template voucher tidak ditemukan. Hubungi admin.',
                'discount'       => 0,
                'is_free_item'   => false,
                'free_barang_id' => null,
            ];
        }

        if ($template->type === 'discount' && $checkoutAmount < $template->min_checkout) {
            return [
                'valid'          => false,
                'message'        => 'Total sewa belum mencapai minimum voucher (Rp '
                    . number_format($template->min_checkout, 0, ',', '.') . ').',
                'discount'       => 0,
                'is_free_item'   => false,
                'free_barang_id' => null,
            ];
        }

        return [
            'valid'          => true,
            'message'        => 'Voucher valid.',
            'discount'       => $template->discount_amount,
            'is_free_item'   => $template->type === 'free_item',
            'free_barang_id' => $template->free_barang_id,
            'voucher'        => $voucher,
        ];
    }

    // ── MARK VOUCHER DIGUNAKAN ────────────────────────────────────────────
    public function useVoucher(UserVoucher $voucher, int $transaksiId): void
    {
        $voucher->update([
            'used_at'              => now(),
            'used_in_transaksi_id' => $transaksiId,
        ]);
    }

    // ── GET REWARD PROFILE ────────────────────────────────────────────────
    public function getProfile(User $user): array
    {
        $reward = $user->getOrCreateReward();

        // Hanya save jika ada perubahan dari autoReset
        if ($reward->autoResetStreakIfNeeded()) {
            $reward->save();
        }

        $streakValues    = UserReward::getStreakXpValues();
        $xpForNextLevel  = self::XP_PER_LEVEL;
        $progressXp      = $reward->current_xp;
        $progressMax     = self::XP_PER_LEVEL;

        return [
            'total_xp'          => $reward->total_xp,
            'current_xp'        => $reward->current_xp,
            'level'             => $reward->level,
            'level_name'        => $reward->level_name,
            'xp_for_next_level' => $progressMax,
            'progress_xp'       => $progressXp,
            'available_boxes'   => $reward->available_boxes,
            'current_streak'    => $reward->current_streak,
            'is_daily_claimed'  => $reward->isDailyClaimedToday(),
            'streak_xp_values'  => $streakValues,
        ];
    }
}
