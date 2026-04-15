<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\User;

class ProfilController extends Controller
{
    public function edit()
    {
        return view('user.profil.edit');
    }

    public function update(Request $request)
    {
        $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'email', Rule::unique('users')->ignore(Auth::id())],
            'phone'  => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if ($user) {
            $user->update($request->only('name', 'email', 'phone', 'alamat'));
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'min:8', 'confirmed'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if ($user) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return back()->with('success', 'Password berhasil diperbarui.');
    }

    public function destroy()
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user) {
            Auth::logout();
            $user->delete();
        }

        return redirect('/')->with('success', 'Akun Anda telah dihapus.');
    }
}
