@extends('user.layouts.user')

@section('title', 'Edit Profil')

@section('content')

<div class="mb-6">
    <p class="text-[10px] uppercase tracking-[0.2em] font-bold mb-1" style="color: #a09880;">PENGATURAN AKUN</p>
    <h1 class="text-2xl font-black tracking-tight" style="color: #2f342e;">Edit Profil</h1>
</div>

<div class="max-w-2xl space-y-4">

    {{-- Update Profil --}}
    <div class="rounded-xl overflow-hidden" x-data="{ loading: false }"
         style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
        <div class="px-4 py-3 flex items-center gap-2" style="background: #f4f2ec; border-bottom: 1px solid rgba(101,94,68,0.08);">
            <span class="material-symbols-outlined text-base" style="color: #655e44;">person</span>
            <p class="text-xs font-black uppercase tracking-widest" style="color: #2f342e;">Informasi Akun</p>
        </div>

        <form method="POST" action="{{ route('user.profil.update') }}" @submit="loading = true" class="p-4 space-y-4">
            @csrf
            @method('PATCH')

            {{-- Avatar --}}
            <div class="flex items-center gap-4 pb-4" style="border-bottom: 1px solid rgba(101,94,68,0.08);">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg, #655e44, #4d4030);">
                    <span class="text-2xl font-black" style="color: #F2E8C6;">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                </div>
                <div>
                    <p class="text-sm font-bold" style="color: #2f342e;">{{ Auth::user()->name }}</p>
                    <p class="text-xs" style="color: #7b776c;">{{ Auth::user()->email }}</p>
                    <p class="text-[10px] uppercase tracking-wider mt-0.5" style="color: #a09880;">
                        Bergabung {{ Auth::user()->created_at->format('d M Y') }}
                    </p>
                </div>
            </div>

            {{-- Name --}}
            <div>
                <label class="block text-[9px] uppercase tracking-widest font-semibold mb-1.5" style="color: #7b776c;">
                    Nama Lengkap <span style="color: #dc2626;">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}" required
                       class="w-full px-3 py-2.5 rounded-xl text-sm focus:outline-none transition-all @error('name') ring-1 ring-red-400 @enderror"
                       style="background: #faf9f5; border: 1px solid rgba(101,94,68,0.2); color: #2f342e;"
                       onfocus="this.style.borderColor='#655e44'; this.style.boxShadow='0 0 0 2px rgba(101,94,68,0.15)';"
                       onblur="this.style.borderColor='rgba(101,94,68,0.2)'; this.style.boxShadow='';">
                @error('name')
                    <p class="text-[10px] mt-1 flex items-center gap-1" style="color: #dc2626;">
                        <span class="material-symbols-outlined text-sm">error</span>{{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="block text-[9px] uppercase tracking-widest font-semibold mb-1.5" style="color: #7b776c;">
                    Email <span style="color: #dc2626;">*</span>
                </label>
                <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}" required
                       class="w-full px-3 py-2.5 rounded-xl text-sm focus:outline-none transition-all @error('email') ring-1 ring-red-400 @enderror"
                       style="background: #faf9f5; border: 1px solid rgba(101,94,68,0.2); color: #2f342e;"
                       onfocus="this.style.borderColor='#655e44'; this.style.boxShadow='0 0 0 2px rgba(101,94,68,0.15)';"
                       onblur="this.style.borderColor='rgba(101,94,68,0.2)'; this.style.boxShadow='';">
                @error('email')
                    <p class="text-[10px] mt-1 flex items-center gap-1" style="color: #dc2626;">
                        <span class="material-symbols-outlined text-sm">error</span>{{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Phone --}}
            <div>
                <label class="block text-[9px] uppercase tracking-widest font-semibold mb-1.5" style="color: #7b776c;">
                    Nomor Telepon
                </label>
                <input type="tel" name="phone" value="{{ old('phone', Auth::user()->phone) }}"
                       placeholder="08xxxxxxxxxx"
                       class="w-full px-3 py-2.5 rounded-xl text-sm focus:outline-none transition-all @error('phone') ring-1 ring-red-400 @enderror"
                       style="background: #faf9f5; border: 1px solid rgba(101,94,68,0.2); color: #2f342e;"
                       onfocus="this.style.borderColor='#655e44'; this.style.boxShadow='0 0 0 2px rgba(101,94,68,0.15)';"
                       onblur="this.style.borderColor='rgba(101,94,68,0.2)'; this.style.boxShadow='';">
                @error('phone')
                    <p class="text-[10px] mt-1 flex items-center gap-1" style="color: #dc2626;">
                        <span class="material-symbols-outlined text-sm">error</span>{{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Alamat --}}
            <div>
                <label class="block text-[9px] uppercase tracking-widest font-semibold mb-1.5" style="color: #7b776c;">
                    Alamat
                </label>
                <textarea name="alamat" rows="3" placeholder="Jalan, kelurahan, kecamatan, kota..."
                          class="w-full px-3 py-2.5 rounded-xl text-sm focus:outline-none transition-all resize-none @error('alamat') ring-1 ring-red-400 @enderror"
                          style="background: #faf9f5; border: 1px solid rgba(101,94,68,0.2); color: #2f342e;"
                          onfocus="this.style.borderColor='#655e44'; this.style.boxShadow='0 0 0 2px rgba(101,94,68,0.15)';"
                          onblur="this.style.borderColor='rgba(101,94,68,0.2)'; this.style.boxShadow='';">{{ old('alamat', Auth::user()->alamat) }}</textarea>
                @error('alamat')
                    <p class="text-[10px] mt-1 flex items-center gap-1" style="color: #dc2626;">
                        <span class="material-symbols-outlined text-sm">error</span>{{ $message }}
                    </p>
                @enderror
            </div>

            <div class="flex justify-end pt-2" style="border-top: 1px solid rgba(101,94,68,0.08);">
                <button type="submit" :disabled="loading"
                        class="flex items-center gap-2 text-[#F2E8C6] font-black text-xs uppercase tracking-widest px-5 py-2.5 rounded-xl transition-all active:scale-[0.97] hover:opacity-90 disabled:opacity-50"
                        style="background: #655e44;">
                    <span class="material-symbols-outlined text-base" x-show="!loading">save</span>
                    <span class="material-symbols-outlined text-base animate-spin" x-show="loading">progress_activity</span>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </form>
    </div>

    {{-- Ubah Password --}}
    <div class="rounded-xl overflow-hidden" x-data="{ loading: false, showCurrent: false, showNew: false, showConfirm: false }"
         style="background: #fff; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
        <div class="px-4 py-3 flex items-center gap-2" style="background: #f4f2ec; border-bottom: 1px solid rgba(101,94,68,0.08);">
            <span class="material-symbols-outlined text-base" style="color: #655e44;">lock</span>
            <p class="text-xs font-black uppercase tracking-widest" style="color: #2f342e;">Ubah Password</p>
        </div>

        <form method="POST" action="{{ route('user.profil.update-password') }}" @submit="loading = true" class="p-4 space-y-4">
            @csrf
            @method('PATCH')

            {{-- Current Password --}}
            <div>
                <label class="block text-[9px] uppercase tracking-widest font-semibold mb-1.5" style="color: #7b776c;">Password Saat Ini</label>
                <div class="relative">
                    <input :type="showCurrent ? 'text' : 'password'" name="current_password"
                           placeholder="Masukkan password saat ini"
                           class="w-full pl-3 pr-10 py-2.5 rounded-xl text-sm focus:outline-none transition-all @error('current_password') ring-1 ring-red-400 @enderror"
                           style="background: #faf9f5; border: 1px solid rgba(101,94,68,0.2); color: #2f342e;"
                           onfocus="this.style.borderColor='#655e44'; this.style.boxShadow='0 0 0 2px rgba(101,94,68,0.15)';"
                           onblur="this.style.borderColor='rgba(101,94,68,0.2)'; this.style.boxShadow='';">
                    <button type="button" @click="showCurrent = !showCurrent"
                            class="absolute right-3 top-1/2 -translate-y-1/2 transition-colors hover:opacity-70"
                            style="color: #7b776c;">
                        <span class="material-symbols-outlined text-base" x-text="showCurrent ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>
                @error('current_password')
                    <p class="text-[10px] mt-1 flex items-center gap-1" style="color: #dc2626;">
                        <span class="material-symbols-outlined text-sm">error</span>{{ $message }}
                    </p>
                @enderror
            </div>

            {{-- New Password --}}
            <div>
                <label class="block text-[9px] uppercase tracking-widest font-semibold mb-1.5" style="color: #7b776c;">
                    Password Baru <span style="color: #a09880; normal-case font-normal text-[9px];">(min. 8 karakter)</span>
                </label>
                <div class="relative">
                    <input :type="showNew ? 'text' : 'password'" name="password"
                           placeholder="Minimal 8 karakter"
                           class="w-full pl-3 pr-10 py-2.5 rounded-xl text-sm focus:outline-none transition-all @error('password') ring-1 ring-red-400 @enderror"
                           style="background: #faf9f5; border: 1px solid rgba(101,94,68,0.2); color: #2f342e;"
                           onfocus="this.style.borderColor='#655e44'; this.style.boxShadow='0 0 0 2px rgba(101,94,68,0.15)';"
                           onblur="this.style.borderColor='rgba(101,94,68,0.2)'; this.style.boxShadow='';">
                    <button type="button" @click="showNew = !showNew"
                            class="absolute right-3 top-1/2 -translate-y-1/2 transition-colors hover:opacity-70"
                            style="color: #7b776c;">
                        <span class="material-symbols-outlined text-base" x-text="showNew ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>
                @error('password')
                    <p class="text-[10px] mt-1 flex items-center gap-1" style="color: #dc2626;">
                        <span class="material-symbols-outlined text-sm">error</span>{{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div>
                <label class="block text-[9px] uppercase tracking-widest font-semibold mb-1.5" style="color: #7b776c;">Konfirmasi Password Baru</label>
                <div class="relative">
                    <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation"
                           placeholder="Ulangi password baru"
                           class="w-full pl-3 pr-10 py-2.5 rounded-xl text-sm focus:outline-none transition-all"
                           style="background: #faf9f5; border: 1px solid rgba(101,94,68,0.2); color: #2f342e;"
                           onfocus="this.style.borderColor='#655e44'; this.style.boxShadow='0 0 0 2px rgba(101,94,68,0.15)';"
                           onblur="this.style.borderColor='rgba(101,94,68,0.2)'; this.style.boxShadow='';">
                    <button type="button" @click="showConfirm = !showConfirm"
                            class="absolute right-3 top-1/2 -translate-y-1/2 transition-colors hover:opacity-70"
                            style="color: #7b776c;">
                        <span class="material-symbols-outlined text-base" x-text="showConfirm ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>
            </div>

            <div class="flex justify-end pt-2" style="border-top: 1px solid rgba(101,94,68,0.08);">
                <button type="submit" :disabled="loading"
                        class="flex items-center gap-2 font-black text-xs uppercase tracking-widest px-5 py-2.5 rounded-xl transition-all active:scale-[0.97] hover:opacity-80 disabled:opacity-50"
                        style="background: #f4f2ec; border: 1px solid rgba(101,94,68,0.3); color: #4d4a3e;">
                    <span class="material-symbols-outlined text-base" x-show="!loading">key</span>
                    <span class="material-symbols-outlined text-base animate-spin" x-show="loading">progress_activity</span>
                    <span x-text="loading ? 'Memperbarui...' : 'Perbarui Password'"></span>
                </button>
            </div>
        </form>
    </div>

    {{-- Danger Zone --}}
    <div class="rounded-xl overflow-hidden" x-data="{ confirm: false }"
         style="background: #fff; border: 1px solid #fecaca; box-shadow: 0 2px 10px rgba(37,29,29,0.07);">
        <div class="px-4 py-3 flex items-center gap-2" style="background: #fef2f2; border-bottom: 1px solid #fecaca;">
            <span class="material-symbols-outlined text-base" style="color: #f87171;">warning</span>
            <p class="text-xs font-black uppercase tracking-widest" style="color: #f87171;">Zona Berbahaya</p>
        </div>
        <div class="p-4">
            <p class="text-xs mb-3 leading-relaxed" style="color: #7b776c;">
                Menghapus akun bersifat permanen. Semua data pesanan dan riwayat akan hilang.
            </p>
            <button @click="confirm = !confirm"
                    class="flex items-center gap-2 text-xs font-black uppercase tracking-widest px-4 py-2 rounded-xl transition-all hover:opacity-80"
                    style="border: 1px solid #fca5a5; color: #dc2626; background: #fef2f2;">
                <span class="material-symbols-outlined text-base">delete_forever</span>
                Hapus Akun
            </button>

            <div x-show="confirm"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-3 rounded-xl p-4"
                 style="background: #fef2f2; border: 1px solid #fca5a5;">
                <p class="text-xs font-black uppercase tracking-widest mb-2" style="color: #991b1b;">⚠ Konfirmasi Penghapusan</p>
                <p class="text-xs mb-3 leading-relaxed" style="color: #7b776c;">
                    Tindakan ini tidak bisa dibatalkan. Yakin ingin menghapus akun?
                </p>
                <form method="POST" action="{{ route('user.profil.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="flex items-center gap-2 text-white text-xs font-black uppercase tracking-widest px-4 py-2.5 rounded-xl transition-all active:scale-[0.97] hover:opacity-90"
                            style="background: #dc2626;">
                        <span class="material-symbols-outlined text-base">delete_forever</span>
                        Ya, Hapus Akun Saya
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection
