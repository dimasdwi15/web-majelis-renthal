<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index()
    {
        $notifikasi = Notifikasi::where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return view('user.notifikasi.index', compact('notifikasi'));
    }

    public function baca(Notifikasi $notifikasi)
    {
        abort_if($notifikasi->user_id !== Auth::id(), 403);
        $notifikasi->update(['dibaca' => true]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    public function bacaSemua()
    {
        Notifikasi::where('user_id', Auth::id())
            ->where('dibaca', false)
            ->update(['dibaca' => true]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
