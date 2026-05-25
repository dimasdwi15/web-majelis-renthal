<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserVoucher;
use App\Models\VoucherTemplate;
use App\Services\RewardsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RewardsController extends Controller
{
    public function __construct(private readonly RewardsService $rewards) {}

    // ── GET /rewards/profile ───────────────────────────────────────────
    public function profile(): JsonResponse
    {
        $user    = Auth::user();
        $profile = $this->rewards->getProfile($user);

        return response()->json([
            'status' => 'success',
            'data'   => $profile,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    // ── POST /rewards/claim-daily ──────────────────────────────────────
    public function claimDaily(): JsonResponse
    {
        $user   = Auth::user();
        $result = $this->rewards->claimDaily($user);

        if (! $result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Sudah diklaim hari ini. Kembali lagi besok!',
            ], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => '+' . $result['xp_earned'] . ' XP dari daily check-in!',
            'data'    => $result,
        ]);
    }

    // ── GET /rewards/voucher-catalog ──────────────────────────────────
    public function voucherCatalog(): JsonResponse
    {
        $templates = VoucherTemplate::where('is_active', true)
            ->whereNotNull('xp_cost')
            ->orderBy('xp_cost')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $templates->map(fn($t) => [
                'id'              => $t->id,
                'code'            => $t->code,
                'title'           => $t->title,
                'description'     => $t->description,
                'type'            => $t->type,
                'rarity'          => $t->rarity,
                'discount_amount' => $t->discount_amount,
                'min_checkout'    => $t->min_checkout,
                'xp_cost'         => $t->xp_cost,
                'valid_days'      => $t->valid_days,
            ]),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    // ── GET /rewards/my-vouchers ───────────────────────────────────────────
    public function myVouchers(): JsonResponse
    {
        $user = Auth::user();

        $vouchers = UserVoucher::with([
            'template',
            'template.freeBarang',
            'freeBarang',
            'mysteryBoxItem',
            'mysteryBoxItem.barang',
        ])
            ->where('user_id', $user->id)
            ->active()
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $vouchers->map(fn($v) => $this->formatVoucher($v)),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    // ── POST /rewards/redeem-voucher ──────────────────────────────────
    public function redeemVoucher(Request $request): JsonResponse
    {
        $request->validate(['template_id' => 'required|integer|exists:voucher_templates,id']);

        $result = $this->rewards->redeemVoucher(Auth::user(), $request->template_id);

        if ($result['success']) {
            $result['voucher']->loadMissing([
                'template',
                'template.freeBarang',
                'freeBarang',
            ]);
        }
        if (! $result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => $result['message'],
            'data'    => $this->formatVoucher($result['voucher']),
        ]);
    }

    // ── POST /rewards/open-box ──────────────────────────────────────────────
    public function openBox(): JsonResponse
    {
        $result = $this->rewards->openMysteryBox(Auth::user());

        if (! $result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => $result['message'],
            'data'    => $this->formatVoucher($result['voucher']),
        ]);
    }

    // ── GET /rewards/xp-logs ──────────────────────────────────────────
    public function xpLogs(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $logs = $user->xpLogs()->latest()->limit(50)->get();

        return response()->json([
            'status' => 'success',
            'data'   => $logs->map(fn($log) => [
                'id'          => $log->id,
                'amount'      => $log->amount,
                'source'      => $log->source,
                'description' => $log->description,
                'created_at'  => $log->created_at->format('d M Y, H:i'),
                'is_earn'     => $log->amount >= 0,
            ]),
        ]);
    }

    // ── POST /rewards/validate-voucher ────────────────────────────────
    public function validateVoucher(Request $request): JsonResponse
    {
        $request->validate([
            'unique_code'     => 'required|string',
            'checkout_amount' => 'required|numeric|min:0',
        ]);

        $result = $this->rewards->applyVoucher(
            Auth::user(),
            $request->unique_code,
            (float) $request->checkout_amount
        );

        if (! $result['valid']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 422);
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'discount'       => $result['discount'],
                'is_free_item'   => $result['is_free_item'],
                'free_barang_id' => $result['free_barang_id'],
            ],
        ]);
    }

    // ── FORMAT HELPER ─────────────────────────────────────────────────────────
    private function formatVoucher(UserVoucher $v): array
    {
        // ── Voucher orphan — kedua FK null ────────────────────────────────────
        if (is_null($v->voucher_template_id) && is_null($v->mystery_box_item_id)) {
            return [
                'id'          => $v->id,
                'unique_code' => $v->unique_code,
                'expires_at'  => $v->expires_at->format('d M Y'),
                'expires_in'  => $v->expires_in,
                'is_used'     => $v->is_used,
                'source'      => 'mystery_box',
                'template'    => [
                    'id'              => null,
                    'code'            => $v->unique_code,
                    'title'           => $v->mystery_title_snapshot ?? 'Mystery Reward',
                    'description'     => 'Hadiah dari Mystery Box',
                    'type'            => 'discount',
                    'rarity'          => 'Common',
                    'discount_amount' => 0,
                    'min_checkout'    => 0,
                    'is_free_item'    => false,
                    'free_barang'     => null,
                ],
                // ✅ FIX IMAGE: Snapshot foto yang sudah disimpan saat pembukaan box
                'imageUrl' => $this->resolveImageUrl($v->mystery_foto_url_snapshot ?? ''),
            ];
        }

        // ── Voucher dari MYSTERY BOX (item masih ada) ─────────────────────────
        if ($v->mystery_box_item_id) {
            $item = $v->mysteryBoxItem;

            // ✅ FIX IMAGE: Urutan prioritas foto:
            //    1. Snapshot (reliable, tidak berubah)
            //    2. Snapshot di item
            //    3. Relasi barang → fotoUtama
            $rawFotoPath = $v->mystery_foto_url_snapshot
                ?? $item?->barang_foto_url_snapshot
                ?? optional($item?->barang?->fotoUtama)->path_foto
                ?? '';

            $fotoUrl = $this->resolveImageUrl($rawFotoPath);

            return [
                'id'          => $v->id,
                'unique_code' => $v->unique_code,
                'expires_at'  => $v->expires_at->format('d M Y'),
                'expires_in'  => $v->expires_in,
                'is_used'     => $v->is_used,
                'source'      => 'mystery_box',
                'template'    => [
                    'id'              => null,
                    'code'            => $v->unique_code,
                    'title'           => $v->mystery_title_snapshot ?? ($item?->title ?? 'Mystery Reward'),
                    'description'     => $item?->description ?? 'Hadiah dari Mystery Box!',
                    'type'            => $item?->type ?? 'discount',
                    'rarity'          => $item?->rarity ?? 'Common',
                    'discount_amount' => $item?->discount_amount ?? 0,
                    'min_checkout'    => $item?->min_checkout ?? 0,
                    'is_free_item'    => ($item?->type === 'free_rental'),
                    // ✅ FIX IMAGE: foto_url di dalam free_barang juga resolved
                    'free_barang'     => ($item?->type === 'free_rental') ? [
                        'id'      => $item->barang_id,
                        'nama'    => $item->barang_nama,
                        // foto_url di sini juga absolut agar bisa diakses mobile via ngrok
                        'foto_url' => $this->resolveImageUrl(
                            optional($item->barang?->fotoUtama)->path_foto ?? ''
                        ),
                    ] : null,
                ],
                // ✅ imageUrl level atas — ini yang dipakai Flutter untuk ditampilkan di RewardRevealWidget
                'imageUrl' => $fotoUrl,
            ];
        }

        // ── Voucher dari VOUCHER TEMPLATES (redeem XP) ────────────────────────
        $t          = $v->template;
        $freeBarang = $v->freeBarang ?? $t?->freeBarang;

        return [
            'id'          => $v->id,
            'unique_code' => $v->unique_code,
            'expires_at'  => $v->expires_at->format('d M Y'),
            'expires_in'  => $v->expires_in,
            'is_used'     => $v->is_used,
            'source'      => 'redeem',
            'template'    => [
                'id'              => $t?->id,
                'code'            => $t?->code,
                'title'           => $v->dynamic_title,
                'description'     => $v->dynamic_description,
                'type'            => $t?->type,
                'rarity'          => $t?->rarity ?? 'Common',
                'discount_amount' => $t?->discount_amount ?? 0,
                'min_checkout'    => $t?->min_checkout ?? 0,
                'is_free_item'    => ($t?->type === 'free_item'),
                'free_barang'     => $freeBarang ? [
                    'id'   => $freeBarang->id,
                    'nama' => $freeBarang->nama,
                ] : null,
            ],
            // Voucher redeem XP tidak punya foto barang
            'imageUrl' => '',
        ];
    }

    /**
     * ✅ FIX IMAGE: Konversi path relatif atau path Storage Laravel
     * menjadi URL absolut yang bisa diakses dari device mobile via ngrok.
     *
     * Kenapa ini perlu:
     *   - asset() di PHP menghasilkan URL dengan APP_URL (bisa 127.0.0.1)
     *   - Storage::url() menghasilkan URL absolut dari APP_URL di .env
     *   - Jika APP_URL diset ke ngrok URL, ini sudah benar
     *   - Jika masih localhost, Flutter akan gagal load gambar
     *
     * SOLUSI TERBAIK: Set APP_URL=https://xxxx.ngrok.io di file .env
     * sehingga Storage::url() otomatis menghasilkan URL ngrok yang benar.
     */
    private function resolveImageUrl(?string $path): string
    {
        if (empty($path)) return '';

        // Sudah URL absolut (https://...) — kembalikan langsung
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // Path relatif storage (mis. "barang/foto/abc.jpg")
        // Storage::url() akan menggunakan APP_URL dari .env
        return Storage::url($path);
    }

    // ── Debug route (hapus di production) ────────────────────────────────────
    public function debugVouchers(): JsonResponse
    {
        $user   = Auth::user();
        $all    = UserVoucher::where('user_id', $user->id)->get();
        $active = UserVoucher::where('user_id', $user->id)->active()->get();

        return response()->json([
            'total_vouchers'  => $all->count(),
            'active_vouchers' => $active->count(),
            'vouchers_detail' => $all->map(fn($v) => [
                'id'                  => $v->id,
                'unique_code'         => $v->unique_code,
                'used_at'             => $v->used_at,
                'expires_at'          => $v->expires_at,
                'voucher_template_id' => $v->voucher_template_id,
                'mystery_box_item_id' => $v->mystery_box_item_id,
                'mystery_foto_url_snapshot' => $v->mystery_foto_url_snapshot,
                'resolved_image_url'  => $this->resolveImageUrl($v->mystery_foto_url_snapshot ?? ''),
                'is_active_scope'     => is_null($v->used_at) && $v->expires_at > now(),
            ]),
        ]);
    }
}
