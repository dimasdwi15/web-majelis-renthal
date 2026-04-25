<x-guest-layout>
{{--
    ╔═══════════════════════════════════════════════════════════════╗
    ║  AUTH — REGISTER (combined login + register, default: daftar) ║
    ║  Design system: Industrial Precision / Overbuilt Utility      ║
    ║  NOTE: This file is identical to login.blade.php except for   ║
    ║        isLogin: false  (register tab active by default)        ║
    ╚═══════════════════════════════════════════════════════════════╝
--}}
<div
    class="min-h-screen flex"
    x-data="{ isLogin: false }"
    x-cloak
>

    {{-- ════════════════════════════════════════════
         LEFT PANEL — Brand / Industrial Visual
    ════════════════════════════════════════════ --}}
    <div class="hidden lg:flex lg:w-5/12 xl:w-1/2 flex-col relative overflow-hidden"
         style="background: #251D1D; min-height: 100vh;">

        <div class="absolute inset-0 panel-texture pointer-events-none"></div>
        <div class="absolute inset-0 panel-accent-lines pointer-events-none"></div>

        <div class="absolute bottom-0 left-0 pulse-ring"
             style="width: 480px; height: 480px; border-radius: 50%;
                    background: radial-gradient(circle, rgba(101,94,68,0.18) 0%, transparent 70%);
                    transform-origin: bottom left;">
        </div>

        <div class="absolute right-0 top-1/4 select-none pointer-events-none"
             style="font-size: 280px; font-weight: 800; color: rgba(242,232,198,0.025);
                    line-height: 1; letter-spacing: -0.05em; font-family: 'Inter', sans-serif;">
            MR
        </div>

        {{-- Top bar --}}
        <div class="relative z-10 flex items-center justify-between px-8 pt-8">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center rounded-lg"
                     style="width:38px;height:38px;background:#655e44;flex-shrink:0;">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2" y="3" width="8" height="6" rx="1" fill="#F2E8C6"/>
                        <rect x="11" y="3" width="7" height="6" rx="1" fill="rgba(242,232,198,0.45)"/>
                        <rect x="2" y="11" width="5" height="6" rx="1" fill="rgba(242,232,198,0.45)"/>
                        <rect x="9" y="11" width="9" height="6" rx="1" fill="#F2E8C6"/>
                    </svg>
                </div>
                <div>
                    <p style="color:#F2E8C6;font-size:13px;font-weight:800;letter-spacing:0.1em;margin:0;line-height:1.1;">
                        MAJELIS
                    </p>
                    <p style="color:rgba(242,232,198,0.45);font-size:10px;font-weight:600;letter-spacing:0.18em;margin:0;line-height:1.1;">
                        RENTAL SYSTEM
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <div style="width:7px;height:7px;border-radius:50%;background:#655e44;"></div>
                <div style="width:7px;height:7px;border-radius:50%;background:rgba(101,94,68,0.45);"></div>
                <div style="width:7px;height:7px;border-radius:50%;background:rgba(101,94,68,0.2);"></div>
            </div>
        </div>

        {{-- Main brand content --}}
        <div class="relative z-10 flex-1 flex flex-col justify-center px-10 xl:px-14">
            <div style="color:rgba(101,94,68,0.8);font-size:10px;font-weight:700;letter-spacing:0.22em;margin-bottom:18px;">
                PLATFORM MANAJEMEN SEWA
            </div>
            <h1 style="color:#F2E8C6;font-size:clamp(32px,3.2vw,46px);font-weight:800;
                       line-height:1.1;letter-spacing:-0.025em;margin:0 0 18px 0;">
                Kelola Sewa<br>dengan<br>Presisi Penuh
            </h1>
            <p style="color:rgba(242,232,198,0.45);font-size:14px;line-height:1.7;max-width:320px;margin-bottom:36px;">
                Sistem rental terintegrasi yang solid, andal, dan dirancang untuk efisiensi operasional maksimal dari hari pertama.
            </p>

            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach([
                    ['Manajemen Stok Real-time',  'Pantau ketersediaan aset secara langsung'],
                    ['Laporan & Analitik',         'Wawasan operasional terperinci'],
                    ['Multi-pengguna & Peran',     'Kontrol akses granular per staf'],
                ] as [$title, $sub])
                <div style="display:flex;align-items:flex-start;gap:12px;">
                    <div style="width:22px;height:22px;border-radius:4px;background:rgba(101,94,68,0.2);
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                        <svg width="11" height="11" viewBox="0 0 11 11" fill="none">
                            <path d="M1.5 5.5L4.5 8.5L9.5 2.5" stroke="#F2E8C6" stroke-width="1.8"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <p style="color:#F2E8C6;font-size:13px;font-weight:600;margin:0;line-height:1.3;">{{ $title }}</p>
                        <p style="color:rgba(242,232,198,0.38);font-size:11px;margin:2px 0 0 0;line-height:1.4;">{{ $sub }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Dot grid --}}
        <div class="relative z-10 px-10 pb-3 xl:px-14">
            <div class="dot-grid">
                @for($i=0; $i<30; $i++)
                <span></span>
                @endfor
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="relative z-10 px-8 pb-8 pt-4">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="height:1px;flex:1;background:rgba(242,232,198,0.08);"></div>
                <span style="color:rgba(242,232,198,0.22);font-size:10px;font-weight:600;letter-spacing:0.2em;">
                    MAJELIS RENTAL © {{ date('Y') }}
                </span>
                <div style="height:1px;flex:1;background:rgba(242,232,198,0.08);"></div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════
         RIGHT PANEL — Auth Forms
    ════════════════════════════════════════════ --}}
    <div class="flex-1 flex items-center justify-center p-5 sm:p-8"
         style="background:#faf9f5;min-height:100vh;">

        <div class="w-full" style="max-width:420px;">

            {{-- Mobile logo --}}
            <div class="lg:hidden text-center mb-8">
                <div class="inline-flex items-center gap-3">
                    <div class="flex items-center justify-center rounded-lg"
                         style="width:34px;height:34px;background:#655e44;">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
                            <rect x="2" y="3" width="8" height="6" rx="1" fill="#F2E8C6"/>
                            <rect x="11" y="3" width="7" height="6" rx="1" fill="rgba(242,232,198,0.45)"/>
                            <rect x="2" y="11" width="5" height="6" rx="1" fill="rgba(242,232,198,0.45)"/>
                            <rect x="9" y="11" width="9" height="6" rx="1" fill="#F2E8C6"/>
                        </svg>
                    </div>
                    <div style="text-align:left;">
                        <p style="color:#251D1D;font-size:13px;font-weight:800;letter-spacing:0.1em;margin:0;line-height:1.1;">MAJELIS</p>
                        <p style="color:rgba(37,29,29,0.4);font-size:9px;font-weight:600;letter-spacing:0.18em;margin:0;line-height:1.1;">RENTAL SYSTEM</p>
                    </div>
                </div>
            </div>

            {{-- Auth Card --}}
            <div class="auth-card rounded-lg overflow-hidden" style="background:#ffffff;">

                {{-- Tab switcher --}}
                <div style="padding:18px 18px 0 18px;">
                    <div style="background:#f4f4ef;border-radius:8px;padding:5px;display:flex;gap:4px;">
                        <button
                            type="button"
                            @click="isLogin = true"
                            style="flex:1;padding:9px 0;font-size:11px;font-weight:700;letter-spacing:0.1em;
                                   border:none;border-radius:5px;cursor:pointer;
                                   font-family:'Inter',sans-serif;transition:all 0.2s ease;background:transparent;"
                            :style="isLogin ? 'background:#fff;color:#251D1D;box-shadow:0 1px 4px rgba(37,29,29,0.12);' : 'background:transparent;color:rgba(101,94,68,0.55);'"
                        >MASUK</button>
                        <button
                            type="button"
                            @click="isLogin = false"
                            style="flex:1;padding:9px 0;font-size:11px;font-weight:700;letter-spacing:0.1em;
                                   border:none;border-radius:5px;cursor:pointer;
                                   font-family:'Inter',sans-serif;transition:all 0.2s ease;background:transparent;"
                            :style="!isLogin ? 'background:#fff;color:#251D1D;box-shadow:0 1px 4px rgba(37,29,29,0.12);' : 'background:transparent;color:rgba(101,94,68,0.55);'"
                        >DAFTAR</button>
                    </div>
                </div>

                {{-- Forms wrapper --}}
                <div style="position:relative;overflow:hidden;">

                    {{-- ─── LOGIN FORM ─── --}}
                    <div
                        x-show="isLogin"
                        x-transition:enter="transition ease-in-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-x-3"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in-out duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 translate-x-3"
                        style="padding:20px 22px 24px 22px;"
                    >
                        <div style="margin-bottom:20px;">
                            <h2 style="font-size:20px;font-weight:800;color:#251D1D;
                                       letter-spacing:-0.02em;margin:0 0 4px 0;">
                                Selamat Datang
                            </h2>
                            <p style="font-size:12px;color:rgba(37,29,29,0.45);margin:0;font-weight:400;">
                                Masuk ke dashboard Majelis Rental
                            </p>
                        </div>

                        <x-auth-session-status class="mb-4" :status="session('status')" />

                        @if(session('success'))
                            <div class="flash-success">{{ session('success') }}</div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div style="margin-bottom:14px;">
                                <label for="l_email" class="lbl">Alamat Email</label>
                                <input id="l_email" type="email" name="email"
                                    value="{{ old('email') }}" required autocomplete="username"
                                    placeholder="nama@email.com" class="inp">
                                @error('email')<span class="err-msg">{{ $message }}</span>@enderror
                            </div>

                            <div style="margin-bottom:16px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                    <label for="l_password" class="lbl" style="margin-bottom:0;">Password</label>
                                    @if(Route::has('password.request'))
                                        <a href="{{ route('password.request') }}"
                                           style="font-size:11px;color:#655e44;font-weight:600;text-decoration:none;"
                                           onmouseover="this.style.color='#4a4530'" onmouseout="this.style.color='#655e44'">
                                            Lupa password?
                                        </a>
                                    @endif
                                </div>
                                <input id="l_password" type="password" name="password"
                                    required autocomplete="current-password"
                                    placeholder="••••••••" class="inp">
                                @error('password')<span class="err-msg">{{ $message }}</span>@enderror
                            </div>

                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                                <input type="checkbox" id="l_remember" name="remember" class="chk">
                                <label for="l_remember" style="font-size:12px;color:rgba(37,29,29,0.55);cursor:pointer;">
                                    Ingat saya di perangkat ini
                                </label>
                            </div>

                            <button type="submit" class="btn-primary">Masuk ke Akun</button>
                        </form>

                        <div style="display:flex;align-items:center;gap:12px;margin:20px 0;">
                            <div class="sep-line"></div>
                            <span style="font-size:10px;color:rgba(37,29,29,0.38);font-weight:600;letter-spacing:0.1em;white-space:nowrap;">
                                ATAU LANJUT DENGAN
                            </span>
                            <div class="sep-line"></div>
                        </div>

                        <a href="/auth/google" class="btn-google">
                            <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            <span style="font-size:13px;font-weight:600;color:#251D1D;">Masuk dengan Google</span>
                        </a>

                        <p style="text-align:center;font-size:12px;color:rgba(37,29,29,0.4);margin:16px 0 0 0;">
                            Belum punya akun?
                            <button type="button" @click="isLogin = false"
                                    style="color:#655e44;font-weight:700;background:none;border:none;cursor:pointer;
                                           font-size:12px;font-family:'Inter',sans-serif;padding:0;"
                                    onmouseover="this.style.color='#4a4530'" onmouseout="this.style.color='#655e44'">
                                Daftar sekarang
                            </button>
                        </p>
                    </div>

                    {{-- ─── REGISTER FORM ─── --}}
                    <div
                        x-show="!isLogin"
                        x-transition:enter="transition ease-in-out duration-300"
                        x-transition:enter-start="opacity-0 translate-x-3"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in-out duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-3"
                        style="padding:20px 22px 24px 22px;"
                    >
                        <div style="margin-bottom:20px;">
                            <h2 style="font-size:20px;font-weight:800;color:#251D1D;
                                       letter-spacing:-0.02em;margin:0 0 4px 0;">
                                Buat Akun Baru
                            </h2>
                            <p style="font-size:12px;color:rgba(37,29,29,0.45);margin:0;font-weight:400;">
                                Daftarkan diri Anda ke sistem Majelis Rental
                            </p>
                        </div>

                        @if($errors->any())
                            <div style="background:#fff1f1;border-radius:6px;padding:10px 14px;
                                        margin-bottom:14px;border-left:3px solid #b91c1c;">
                                @foreach($errors->all() as $error)
                                    <p style="font-size:12px;color:#b91c1c;margin:2px 0;font-weight:500;">{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <div style="margin-bottom:13px;">
                                <label for="r_name" class="lbl">Nama Lengkap</label>
                                <input id="r_name" type="text" name="name"
                                    value="{{ old('name') }}" required autofocus autocomplete="name"
                                    placeholder="Nama sesuai identitas" class="inp">
                                @error('name')<span class="err-msg">{{ $message }}</span>@enderror
                            </div>

                            <div style="margin-bottom:13px;">
                                <label for="r_email" class="lbl">Alamat Email</label>
                                <input id="r_email" type="email" name="email"
                                    value="{{ old('email') }}" required autocomplete="username"
                                    placeholder="nama@email.com" class="inp">
                                @error('email')<span class="err-msg">{{ $message }}</span>@enderror
                            </div>

                            <div style="margin-bottom:13px;">
                                <label for="r_phone" class="lbl">No. Telepon</label>
                                <input id="r_phone" type="text" name="phone"
                                    value="{{ old('phone') }}" autocomplete="tel"
                                    placeholder="08xx-xxxx-xxxx" class="inp">
                                @error('phone')<span class="err-msg">{{ $message }}</span>@enderror
                            </div>

                            <div style="margin-bottom:13px;">
                                <label for="r_alamat" class="lbl">Alamat Lengkap</label>
                                <textarea id="r_alamat" name="alamat" rows="2"
                                    placeholder="Jl. Nama Jalan, Kota, Provinsi"
                                    class="inp">{{ old('alamat') }}</textarea>
                                @error('alamat')<span class="err-msg">{{ $message }}</span>@enderror
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px;">
                                <div>
                                    <label for="r_password" class="lbl">Password</label>
                                    <input id="r_password" type="password" name="password"
                                        required autocomplete="new-password"
                                        placeholder="••••••••" class="inp">
                                    @error('password')<span class="err-msg">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label for="r_password_conf" class="lbl">Konfirmasi</label>
                                    <input id="r_password_conf" type="password" name="password_confirmation"
                                        required autocomplete="new-password"
                                        placeholder="••••••••" class="inp">
                                    @error('password_confirmation')
                                        <span class="err-msg">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit" class="btn-primary">Buat Akun Sekarang</button>
                        </form>

                        <div style="display:flex;align-items:center;gap:12px;margin:20px 0;">
                            <div class="sep-line"></div>
                            <span style="font-size:10px;color:rgba(37,29,29,0.38);font-weight:600;letter-spacing:0.1em;white-space:nowrap;">
                                ATAU LANJUT DENGAN
                            </span>
                            <div class="sep-line"></div>
                        </div>

                        <a href="/auth/google" class="btn-google">
                            <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            <span style="font-size:13px;font-weight:600;color:#251D1D;">Daftar dengan Google</span>
                        </a>

                        <p style="text-align:center;font-size:12px;color:rgba(37,29,29,0.4);margin:16px 0 0 0;">
                            Sudah punya akun?
                            <button type="button" @click="isLogin = true"
                                    style="color:#655e44;font-weight:700;background:none;border:none;cursor:pointer;
                                           font-size:12px;font-family:'Inter',sans-serif;padding:0;"
                                    onmouseover="this.style.color='#4a4530'" onmouseout="this.style.color='#655e44'">
                                Masuk di sini
                            </button>
                        </p>
                    </div>

                </div>
                {{-- end forms wrapper --}}

            </div>
            {{-- end auth card --}}

        </div>
    </div>

</div>
</x-guest-layout>
