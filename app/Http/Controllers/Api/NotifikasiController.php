<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API Controller untuk notifikasi Flutter.
 * Terpisah dari User\NotifikasiController (web/blade).
 */
class NotifikasiController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // GET /api/notifikasi
    // Ambil daftar notifikasi milik user yang login.
    // Query param: ?tipe=transaksi,pembayaran,denda,pengingat
    //              ?belum_dibaca=1  (filter hanya yang belum dibaca)
    //              ?limit=30        (default 30, max 100)
    // ─────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $limit = min((int) $request->query('limit', 30), 100);

        $query = Notifikasi::where('user_id', Auth::id())
            ->latest();

        // Filter tipe (opsional, pisahkan dengan koma)
        if ($request->filled('tipe')) {
            $tipes = explode(',', $request->query('tipe'));
            $query->whereIn('tipe', $tipes);
        }

        // Filter hanya belum dibaca
        if ($request->boolean('belum_dibaca')) {
            $query->where('dibaca', false);
        }

        $notifikasi = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data'    => $notifikasi->map(fn($n) => $this->format($n)),
            'unread'  => Notifikasi::where('user_id', Auth::id())
                            ->where('dibaca', false)
                            ->count(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // PATCH /api/notifikasi/{id}/baca
    // Tandai satu notifikasi sebagai sudah dibaca.
    // ─────────────────────────────────────────────────────────────────
    public function baca(Notifikasi $notifikasi)
    {
        // Pastikan notifikasi milik user sendiri
        if ($notifikasi->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $notifikasi->update(['dibaca' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca.',
            'data'    => $this->format($notifikasi->fresh()),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // PATCH /api/notifikasi/baca-semua
    // Tandai semua notifikasi milik user sebagai sudah dibaca.
    // ─────────────────────────────────────────────────────────────────
    public function bacaSemua()
    {
        Notifikasi::where('user_id', Auth::id())
            ->where('dibaca', false)
            ->update(['dibaca' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi telah ditandai dibaca.',
            'unread'  => 0,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // DELETE /api/notifikasi/{id}
    // Hapus satu notifikasi milik user.
    // ─────────────────────────────────────────────────────────────────
    public function hapus(Notifikasi $notifikasi)
    {
        if ($notifikasi->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $notifikasi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi dihapus.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // DELETE /api/notifikasi/hapus-semua
    // Hapus semua notifikasi milik user.
    // ─────────────────────────────────────────────────────────────────
    public function hapusSemua()
    {
        Notifikasi::where('user_id', Auth::id())->delete();

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi dihapus.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/notifikasi/unread-count
    // Hanya kembalikan jumlah notifikasi belum dibaca (ringan untuk polling).
    // ─────────────────────────────────────────────────────────────────
    public function unreadCount()
    {
        $count = Notifikasi::where('user_id', Auth::id())
            ->where('dibaca', false)
            ->count();

        return response()->json([
            'success' => true,
            'unread'  => $count,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helper: format satu notifikasi ke array response
    // ─────────────────────────────────────────────────────────────────
    private function format(Notifikasi $n): array
    {
        return [
            'id'         => $n->id,
            'judul'      => $n->judul,
            'pesan'      => $n->pesan,
            'tipe'       => $n->tipe,
            'dibaca'     => (bool) $n->dibaca,
            'data'       => $n->data,
            'created_at' => $n->created_at?->toIso8601String(),
        ];
    }
}
