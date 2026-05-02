<x-guest-layout>
    <div class="auth-root" x-data="{
        isLogin: false,
        showLP: false,
        showRP: false,
        showRC: false,
    }" x-cloak>

        {{-- ═══════════ BACKGROUND ═══════════ --}}
        <div class="bg-texture"></div>

        {{-- Static geo decoration --}}
        <div class="bg-geo">
            <svg width="700" height="700" viewBox="0 0 440 440" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="220" cy="220" r="200" stroke="rgba(101,94,68,0.12)" stroke-width="1"
                    stroke-dasharray="6 10" />
                @for ($i = 0; $i < 24; $i++)
                    <line x1="{{ 220 + 192 * cos(deg2rad($i * 15)) }}" y1="{{ 220 + 192 * sin(deg2rad($i * 15)) }}"
                        x2="{{ 220 + 202 * cos(deg2rad($i * 15)) }}" y2="{{ 220 + 202 * sin(deg2rad($i * 15)) }}"
                        stroke="rgba(101,94,68,0.2)" stroke-width="1.2" />
                @endfor

                <circle cx="220" cy="220" r="152" stroke="rgba(101,94,68,0.1)" stroke-width="1"
                    stroke-dasharray="3 8" />
                @for ($i = 0; $i < 8; $i++)
                    <rect x="{{ 220 + 152 * cos(deg2rad($i * 45)) - 3 }}"
                        y="{{ 220 + 152 * sin(deg2rad($i * 45)) - 3 }}" width="6" height="6"
                        fill="rgba(101,94,68,0.2)"
                        transform="rotate(45 {{ 220 + 152 * cos(deg2rad($i * 45)) }} {{ 220 + 152 * sin(deg2rad($i * 45)) }})" />
                @endfor

                <circle cx="220" cy="220" r="110" stroke="rgba(101,94,68,0.07)" stroke-width="1" />
                <circle cx="220" cy="220" r="84" stroke="rgba(101,94,68,0.1)" stroke-width="1.5" />

                <line x1="20" y1="220" x2="420" y2="220" stroke="rgba(101,94,68,0.06)"
                    stroke-width="1" />
                <line x1="220" y1="20" x2="220" y2="420" stroke="rgba(101,94,68,0.06)"
                    stroke-width="1" />
                <line x1="78" y1="78" x2="362" y2="362" stroke="rgba(101,94,68,0.04)"
                    stroke-width="1" />
                <line x1="362" y1="78" x2="78" y2="362" stroke="rgba(101,94,68,0.04)"
                    stroke-width="1" />

                @php
                    $hex = '';
                    for ($i = 0; $i < 6; $i++) {
                        $angle = deg2rad(30 + $i * 60);
                        $x = 220 + 56 * cos($angle);
                        $y = 220 + 56 * sin($angle);
                        $hex .= ($i === 0 ? 'M' : 'L') . round($x, 2) . ',' . round($y, 2) . ' ';
                    }
                    $hex .= 'Z';
                @endphp
                <path d="{{ $hex }}" stroke="rgba(101,94,68,0.18)" stroke-width="1" fill="none" />

                @php
                    $hex2 = '';
                    for ($i = 0; $i < 6; $i++) {
                        $angle = deg2rad(0 + $i * 60);
                        $x = 220 + 36 * cos($angle);
                        $y = 220 + 36 * sin($angle);
                        $hex2 .= ($i === 0 ? 'M' : 'L') . round($x, 2) . ',' . round($y, 2) . ' ';
                    }
                    $hex2 .= 'Z';
                @endphp
                <path d="{{ $hex2 }}" stroke="rgba(101,94,68,0.22)" stroke-width="1"
                    fill="rgba(101,94,68,0.04)" />
                <circle cx="220" cy="220" r="5" fill="rgba(101,94,68,0.2)" />
                <circle cx="220" cy="220" r="2" fill="rgba(101,94,68,0.4)" />

                @for ($q = 0; $q < 4; $q++)
                    <path
                        d="M {{ 220 + 124 * cos(deg2rad($q * 90 + 10)) }} {{ 220 + 124 * sin(deg2rad($q * 90 + 10)) }}
                   A 124 124 0 0 1 {{ 220 + 124 * cos(deg2rad($q * 90 + 80)) }} {{ 220 + 124 * sin(deg2rad($q * 90 + 80)) }}"
                        stroke="rgba(101,94,68,0.09)" stroke-width="2" fill="none" stroke-linecap="round" />
                @endfor
            </svg>
        </div>

        <div class="bg-watermark">MR</div>

        {{-- ═══════════ TOP NAV ═══════════ --}}
        <div class="top-nav">
            <div class="brand-logo">
                <div class="brand-icon">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
                        <rect x="2" y="3" width="8" height="6" rx="1" fill="#655e44" />
                        <rect x="11" y="3" width="7" height="6" rx="1" fill="rgba(101,94,68,0.4)" />
                        <rect x="2" y="11" width="5" height="6" rx="1" fill="rgba(101,94,68,0.4)" />
                        <rect x="9" y="11" width="9" height="6" rx="1" fill="#655e44" />
                    </svg>
                </div>
                <div>
                    <p
                        style="color:#4a4530;font-size:12px;font-weight:800;letter-spacing:0.14em;margin:0;line-height:1.15;">
                        MAJELIS</p>
                    <p
                        style="color:rgba(101,94,68,0.5);font-size:9px;font-weight:600;letter-spacing:0.22em;margin:0;line-height:1.2;">
                        RENTAL SYSTEM</p>
                </div>
            </div>
            <div class="brand-dots">
                <div class="brand-dot" style="background:#655e44;"></div>
                <div class="brand-dot" style="background:rgba(101,94,68,0.35);"></div>
                <div class="brand-dot" style="background:rgba(101,94,68,0.14);"></div>
            </div>
        </div>

        {{-- ═══════════ AUTH WRAPPER ═══════════ --}}
        <div class="auth-wrapper">
            <div class="auth-card">

                {{-- ── Dark Header + Tabs ── --}}
                <div class="card-header">
                    <div class="card-header-texture"></div>
                    <div class="card-header-accent"></div>

                    <div
                        style="position:relative;z-index:2;display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                        <div
                            style="width:7px;height:7px;border-radius:50%;background:rgba(242,232,198,0.25);flex-shrink:0;">
                        </div>
                        <p
                            style="color:rgba(242,232,198,0.35);font-size:9px;font-weight:700;letter-spacing:0.22em;margin:0;text-transform:uppercase;">
                            Majelis Rental — Sistem Manajemen
                        </p>
                    </div>

                    <div class="tab-track">
                        <button type="button" @click="isLogin = true" :class="isLogin ? 'active' : ''"
                            class="tab-btn">Masuk</button>
                        <button type="button" @click="isLogin = false" :class="!isLogin ? 'active' : ''"
                            class="tab-btn">Daftar</button>
                    </div>
                </div>

                {{-- ── Forms ── --}}
                <div class="forms-grid">

                    {{-- ─ LOGIN ─ --}}
                    <div x-show="isLogin" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-x-3"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 translate-x-3" style="padding:26px 26px 30px 26px;">
                        <div style="margin-bottom:22px;">
                            <h2
                                style="font-size:21px;font-weight:900;color:#251D1D;letter-spacing:-0.03em;margin:0 0 5px 0;">
                                Selamat Datang
                            </h2>
                            <p style="font-size:12px;color:rgba(37,29,29,0.4);margin:0;line-height:1.5;">
                                Masuk ke dashboard Majelis Rental
                            </p>
                        </div>

                        <x-auth-session-status class="mb-4" :status="session('status')" />

                        @if (session('success'))
                            <div class="flash-success">{{ session('success') }}</div>
                        @endif

                        @if (
                            $errors->any() &&
                                !$errors->has('name') &&
                                !$errors->has('phone') &&
                                !$errors->has('alamat') &&
                                !$errors->has('password_confirmation'))
                            <div class="flash-error">
                                @foreach ($errors->all() as $error)
                                    <p style="font-size:12px;color:#b91c1c;margin:2px 0;font-weight:500;">
                                        {{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div style="margin-bottom:14px;">
                                <label for="l_email" class="lbl">Alamat Email</label>
                                <input id="l_email" type="email" name="email" value="{{ old('email') }}"
                                    required autofocus autocomplete="username" placeholder="nama@email.com"
                                    class="inp">
                                @error('email')
                                    <span class="err-msg">{{ $message }}</span>
                                @enderror
                            </div>

                            <div style="margin-bottom:16px;">
                                <div
                                    style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                    <label for="l_password" class="lbl" style="margin-bottom:0;">Password</label>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}"
                                            style="font-size:11px;color:#655e44;font-weight:600;text-decoration:none;transition:color 0.15s;"
                                            onmouseover="this.style.color='#4a4530'"
                                            onmouseout="this.style.color='#655e44'">
                                            Lupa password?
                                        </a>
                                    @endif
                                </div>
                                <div class="inp-wrap">
                                    <input id="l_password" :type="showLP ? 'text' : 'password'" name="password"
                                        required autocomplete="current-password" placeholder="••••••••"
                                        class="inp">
                                    <button type="button" class="eye-btn" @click="showLP = !showLP" tabindex="-1">
                                        <svg x-show="!showLP" width="16" height="16" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        <svg x-show="showLP" width="16" height="16" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path
                                                d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                            <path
                                                d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                            <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                                            <line x1="1" y1="1" x2="23" y2="23" />
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="err-msg">{{ $message }}</span>
                                @enderror
                            </div>

                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                                <input type="checkbox" id="l_remember" name="remember" class="chk">
                                <label for="l_remember"
                                    style="font-size:12px;color:rgba(37,29,29,0.45);cursor:pointer;user-select:none;">
                                    Ingat saya di perangkat ini
                                </label>
                            </div>

                            <button type="submit" class="btn-primary">Masuk ke Akun</button>
                        </form>

                        <div style="display:flex;align-items:center;gap:12px;margin:20px 0 16px;">
                            <div class="sep-line"></div>
                            <span
                                style="font-size:10px;color:rgba(37,29,29,0.3);font-weight:700;letter-spacing:0.1em;white-space:nowrap;">ATAU</span>
                            <div class="sep-line"></div>
                        </div>

                        <a type="button" class="btn-google">
                            <svg width="17" height="17" viewBox="0 0 24 24">
                                <path
                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                    fill="#4285F4" />
                                <path
                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                    fill="#34A853" />
                                <path
                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                    fill="#FBBC05" />
                                <path
                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                    fill="#EA4335" />
                            </svg>
                            <span>Masuk dengan Google</span>
                        </a>

                        <p style="text-align:center;font-size:12px;color:rgba(37,29,29,0.35);margin:18px 0 0 0;">
                            Belum punya akun?
                            <button type="button" @click="isLogin = false"
                                style="color:#655e44;font-weight:700;background:none;border:none;cursor:pointer;font-size:12px;font-family:'Inter',sans-serif;padding:0;transition:color 0.15s;"
                                onmouseover="this.style.color='#4a4530'" onmouseout="this.style.color='#655e44'">
                                Daftar sekarang
                            </button>
                        </p>
                    </div>
                    {{-- end login --}}


                    {{-- ─ REGISTER ─ --}}
                    <div x-show="!isLogin" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-x-3"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-3" style="padding:26px 26px 30px 26px;">
                        <div style="margin-bottom:18px;">
                            <h2
                                style="font-size:21px;font-weight:900;color:#251D1D;letter-spacing:-0.03em;margin:0 0 5px 0;">
                                Buat Akun Baru
                            </h2>
                            <p style="font-size:12px;color:rgba(37,29,29,0.4);margin:0;line-height:1.5;">
                                Daftarkan diri Anda ke sistem Majelis Rental
                            </p>
                        </div>

                        @if ($errors->has('name') || $errors->has('phone') || $errors->has('alamat') || $errors->has('password_confirmation'))
                            <div class="flash-error">
                                @foreach ($errors->all() as $error)
                                    <p style="font-size:12px;color:#b91c1c;margin:2px 0;font-weight:500;">
                                        {{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <div style="margin-bottom:12px;">
                                <label for="r_name" class="lbl">Nama Lengkap</label>
                                <input id="r_name" type="text" name="name" value="{{ old('name') }}"
                                    required autocomplete="name" placeholder="Nama sesuai identitas" class="inp">
                                @error('name')
                                    <span class="err-msg">{{ $message }}</span>
                                @enderror
                            </div>

                            <div style="margin-bottom:12px;">
                                <label for="r_email" class="lbl">Alamat Email</label>
                                <input id="r_email" type="email" name="email" value="{{ old('email') }}"
                                    required autocomplete="username" placeholder="nama@email.com" class="inp">
                                @error('email')
                                    <span class="err-msg">{{ $message }}</span>
                                @enderror
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                                <div>
                                    <label for="r_phone" class="lbl">No. Telepon</label>
                                    <input id="r_phone" type="text" name="phone" value="{{ old('phone') }}"
                                        autocomplete="tel" placeholder="08xx-xxxx-xxxx" class="inp">
                                    @error('phone')
                                        <span class="err-msg">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label for="r_alamat" class="lbl">Alamat</label>
                                    <input id="r_alamat" type="text" name="alamat" value="{{ old('alamat') }}"
                                        placeholder="Kota, Provinsi" class="inp">
                                    @error('alamat')
                                        <span class="err-msg">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px;">
                                <div>
                                    <label for="r_password" class="lbl">Password</label>
                                    <div class="inp-wrap">
                                        <input id="r_password" :type="showRP ? 'text' : 'password'" name="password"
                                            required autocomplete="new-password" placeholder="••••••••"
                                            class="inp">
                                        <button type="button" class="eye-btn" @click="showRP = !showRP"
                                            tabindex="-1">
                                            <svg x-show="!showRP" width="15" height="15" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                            <svg x-show="showRP" width="15" height="15" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path
                                                    d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                                <path
                                                    d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                                                <line x1="1" y1="1" x2="23" y2="23" />
                                            </svg>
                                        </button>
                                    </div>
                                    @error('password')
                                        <span class="err-msg">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label for="r_password_confirmation" class="lbl">Konfirmasi</label>
                                    <div class="inp-wrap">
                                        <input id="r_password_confirmation" :type="showRC ? 'text' : 'password'"
                                            name="password_confirmation" required autocomplete="new-password"
                                            placeholder="••••••••" class="inp">
                                        <button type="button" class="eye-btn" @click="showRC = !showRC"
                                            tabindex="-1">
                                            <svg x-show="!showRC" width="15" height="15" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                            <svg x-show="showRC" width="15" height="15" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path
                                                    d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                                <path
                                                    d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                                                <line x1="1" y1="1" x2="23" y2="23" />
                                            </svg>
                                        </button>
                                    </div>
                                    @error('password_confirmation')
                                        <span class="err-msg">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit" class="btn-primary">Buat Akun Sekarang</button>
                        </form>

                        <div style="display:flex;align-items:center;gap:12px;margin:18px 0 16px;">
                            <div class="sep-line"></div>
                            <span
                                style="font-size:10px;color:rgba(37,29,29,0.3);font-weight:700;letter-spacing:0.1em;white-space:nowrap;">ATAU</span>
                            <div class="sep-line"></div>
                        </div>

                        <a href="/auth/google" class="btn-google">
                            <svg width="17" height="17" viewBox="0 0 24 24">
                                <path
                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                    fill="#4285F4" />
                                <path
                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                    fill="#34A853" />
                                <path
                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                    fill="#FBBC05" />
                                <path
                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                    fill="#EA4335" />
                            </svg>
                            <span>Daftar dengan Google</span>
                        </a>

                        <p style="text-align:center;font-size:12px;color:rgba(37,29,29,0.35);margin:18px 0 0 0;">
                            Sudah punya akun?
                            <button type="button" @click="isLogin = true"
                                style="color:#655e44;font-weight:700;background:none;border:none;cursor:pointer;font-size:12px;font-family:'Inter',sans-serif;padding:0;transition:color 0.15s;"
                                onmouseover="this.style.color='#4a4530'" onmouseout="this.style.color='#655e44'">
                                Masuk di sini
                            </button>
                        </p>
                    </div>
                    {{-- end register --}}

                </div>
                {{-- end forms-grid --}}

            </div>
            {{-- end auth-card --}}

        </div>
        {{-- end auth-wrapper --}}

        {{-- ═══════════ BOTTOM FOOTER ═══════════ --}}
        <div class="bottom-footer">
            <div class="footer-line"></div>
            <span class="footer-text">MAJELIS RENTAL © {{ date('Y') }}</span>
            <div class="footer-line"></div>
        </div>

        {{-- ═══════════ FIREBASE GOOGLE AUTH ═══════════ --}}
        <script type="module">
            import {
                initializeApp
            } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
            import {
                getAuth,
                signInWithPopup,
                GoogleAuthProvider
            }
            from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js';

            const firebaseConfig = {
                apiKey: "AIzaSyDp_ab3rpM2o-v4EK6AdNkyKzAqIQk0m00",
                authDomain: "majelis-rental.firebaseapp.com",
                projectId: "majelis-rental",
                storageBucket: "majelis-rental.firebasestorage.app",
                messagingSenderId: "443174553074",
                appId: "1:443174553074:web:65582958d94169b68dd76b",
                measurementId: "G-90552R2KL1"
            };

            const app = initializeApp(firebaseConfig);
            const auth = getAuth(app);
            const provider = new GoogleAuthProvider();

            // Selalu tampilkan account picker
            provider.setCustomParameters({
                prompt: 'select_account'
            });

            async function googleSignIn(buttonEl) {
                const original = buttonEl.innerHTML;
                buttonEl.innerHTML = '<span style="font-size:12px;">Menghubungkan...</span>';
                buttonEl.style.pointerEvents = 'none';

                try {
                    const result = await signInWithPopup(auth, provider);
                    const idToken = await result.user.getIdToken();

                    const res = await fetch('{{ route('auth.google.token') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            id_token: idToken
                        }),
                    });

                    const data = await res.json();

                    if (data.success) {
                        // Redirect — tidak perlu restore tombol
                        window.location.href = data.redirect;
                        return;
                    }

                    alert('Login gagal: ' + data.message);

                } catch (err) {
                    // Popup ditutup user — diam saja tanpa alert
                    const diabaikan = [
                        'auth/popup-closed-by-user',
                        'auth/cancelled-popup-request',
                        'auth/user-cancelled',
                    ];

                    if (!diabaikan.includes(err.code)) {
                        console.error(err);
                        alert('Terjadi kesalahan. Silakan coba lagi.');
                    }

                } finally {
                    // Selalu kembalikan tombol ke semula kecuali redirect sukses
                    buttonEl.innerHTML = original;
                    buttonEl.style.pointerEvents = 'auto';
                }
            }

            document.querySelectorAll('.btn-google').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    googleSignIn(btn);
                });
            });
        </script>

    </div>
</x-guest-layout>
