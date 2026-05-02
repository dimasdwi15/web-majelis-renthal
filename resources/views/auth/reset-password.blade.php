<x-guest-layout>
{{--
    reset-password.blade.php
    Design System: Industrial Precision — "The Overbuilt Utility"
--}}

<style>
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: #f4f4ef; }
    ::-webkit-scrollbar-thumb { background: #655e44; border-radius: 2px; }

    .btn-industrial {
        background: linear-gradient(160deg, #7a7258 0%, #655e44 50%, #4a4530 100%);
        transition: all 0.2s ease;
    }
    .btn-industrial:hover:not(:disabled) {
        background: linear-gradient(160deg, #8a8268 0%, #756e54 50%, #5a5540 100%);
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(37,29,29,0.18);
    }
    .btn-industrial:active:not(:disabled) { transform: translateY(0); box-shadow: none; }
    .btn-industrial:disabled { opacity: 0.4; cursor: not-allowed; }

    .header-texture {
        background-image: repeating-linear-gradient(
            90deg, transparent, transparent 2px,
            rgba(242,232,198,0.015) 2px, rgba(242,232,198,0.015) 4px
        );
    }

    .field-input {
        width: 100%;
        padding: 11px 14px;
        border-radius: 8px;
        border: 1.5px solid rgba(101,94,68,0.2);
        background-color: #f4f4ef;
        color: #251D1D;
        font-size: 14px;
        outline: none;
        transition: all 0.15s ease;
        font-family: inherit;
    }
    .field-input:focus {
        border-color: #655e44;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(101,94,68,0.1);
    }
    .field-input::placeholder { color: rgba(37,29,29,0.3); }
    .field-input.is-error { border-color: #9e422c; background-color: #fdf2ef; }

    .toggle-eye {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: rgba(37,29,29,0.3);
        background: none;
        border: none;
        padding: 2px;
        transition: color 0.15s;
    }
    .toggle-eye:hover { color: #655e44; }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }
    .pulse-dot { animation: pulse-dot 1.2s ease-in-out infinite; }

    @keyframes fade-up {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fade-up 0.35s ease forwards; }

    /* Password strength bar */
    .strength-bar {
        height: 3px;
        border-radius: 2px;
        transition: width 0.3s ease, background-color 0.3s ease;
    }
</style>

<div
    class="min-h-screen flex flex-col"
    style="background-color:#f4f4ef;"
    x-data="{
        showPass: false,
        showConfirm: false,
        password: '',
        confirm: '',

        get strength() {
            const p = this.password;
            if (p.length === 0) return 0;
            let s = 0;
            if (p.length >= 8)  s++;
            if (/[A-Z]/.test(p)) s++;
            if (/[0-9]/.test(p)) s++;
            if (/[^A-Za-z0-9]/.test(p)) s++;
            return s;
        },
        get strengthLabel() {
            return ['', 'Lemah', 'Cukup', 'Kuat', 'Sangat Kuat'][this.strength];
        },
        get strengthColor() {
            return ['', '#9e422c', '#ca8a04', '#655e44', '#16a34a'][this.strength];
        },
        get strengthWidth() {
            return ['0%', '25%', '50%', '75%', '100%'][this.strength];
        },
        get passwordMatch() {
            return this.confirm.length > 0 && this.password === this.confirm;
        },
        get canSubmit() {
            return this.strength >= 2 && this.passwordMatch;
        },
    }"
    x-cloak
>

    {{-- ═══════ TOP COMMAND BAR ═══════ --}}
    <div class="header-texture flex items-center justify-between px-6 py-4" style="background-color:#251D1D;">
        <div class="flex items-center gap-3">
            <div class="grid grid-cols-2 gap-[3px] w-[18px]">
                <div class="h-[7px] rounded-[1px]" style="background:#655e44;"></div>
                <div class="h-[7px] rounded-[1px]" style="background:rgba(101,94,68,0.4);"></div>
                <div class="h-[7px] rounded-[1px]" style="background:rgba(101,94,68,0.4);"></div>
                <div class="h-[7px] rounded-[1px]" style="background:#655e44;"></div>
            </div>
            <div>
                <p class="text-[11px] font-black tracking-[0.18em] leading-none" style="color:#f2e8c6;">MAJELIS</p>
                <p class="text-[8px] font-semibold tracking-[0.24em] leading-none mt-[3px]" style="color:rgba(242,232,198,0.35);">RENTAL SYSTEM</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 rounded" style="background:rgba(242,232,198,0.07);">
            <div class="w-1.5 h-1.5 rounded-full pulse-dot" style="background:#655e44;"></div>
            <span class="text-[9px] font-bold tracking-[0.2em] uppercase" style="color:rgba(242,232,198,0.4);">
                Password Reset
            </span>
        </div>
    </div>

    {{-- ═══════ CONTENT ═══════ --}}
    <div class="flex-1 flex items-center justify-center px-4 py-10">
        <div class="w-full fade-up" style="max-width:440px;">

            {{-- Main card --}}
            <div class="rounded-xl overflow-hidden"
                 style="background-color:#faf9f5;box-shadow:0 20px 40px rgba(37,29,29,0.06);">

                {{-- Card header --}}
                <div class="header-texture" style="background-color:#251D1D;">
                    <div class="flex h-[3px]">
                        <div class="flex-[5]" style="background:#655e44;"></div>
                        <div class="flex-[3]" style="background:#4a4530;"></div>
                        <div class="flex-[2]" style="background:#2c2416;"></div>
                    </div>
                    <div class="px-7 pt-6 pb-6">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-[6px] h-[6px] rounded-full" style="background:rgba(242,232,198,0.2);"></div>
                            <span class="text-[9px] font-bold tracking-[0.24em] uppercase" style="color:rgba(242,232,198,0.3);">
                                Majelis Rental — Buat Password Baru
                            </span>
                        </div>
                        <h1 class="text-[22px] font-black tracking-tight leading-tight" style="color:#f2e8c6;">
                            Reset Password
                        </h1>
                        <p class="text-[12px] mt-1.5 leading-relaxed" style="color:rgba(242,232,198,0.4);">
                            Buat password baru yang kuat untuk akun Anda.
                        </p>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-7 py-8">

                    {{-- Error global --}}
                    @if ($errors->any())
                        <div class="mb-5 px-4 py-3 rounded-lg flex items-start gap-2.5"
                             style="background-color:#fdf2ef;border-left:3px solid #9e422c;">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                 stroke="#9e422c" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <p class="text-[12px] font-medium" style="color:#9e422c;">{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.store') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">
                        <input type="hidden" name="email" value="{{ old('email', session('email', $request->email ?? '')) }}">

                        {{-- Password --}}
                        <div class="mb-5">
                            <label for="password"
                                   class="block text-[10px] font-bold tracking-[0.16em] uppercase mb-2"
                                   style="color:rgba(37,29,29,0.4);">
                                Password Baru
                            </label>
                            <div class="relative">
                                <input
                                    id="password"
                                    name="password"
                                    :type="showPass ? 'text' : 'password'"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Minimal 8 karakter"
                                    x-model="password"
                                    class="field-input {{ $errors->has('password') ? 'is-error' : '' }}"
                                    style="padding-right:42px;"
                                >
                                <button type="button" class="toggle-eye" @click="showPass = !showPass">
                                    <svg x-show="!showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <svg x-show="showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Strength bar --}}
                            <div x-show="password.length > 0" class="mt-2">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 rounded-full overflow-hidden" style="background:rgba(101,94,68,0.1);height:3px;">
                                        <div class="strength-bar h-full rounded-full"
                                             :style="`width:${strengthWidth};background:${strengthColor};`"></div>
                                    </div>
                                    <span class="text-[10px] font-bold"
                                          :style="`color:${strengthColor};`"
                                          x-text="strengthLabel"></span>
                                </div>
                                <p class="text-[10px] mt-1" style="color:rgba(37,29,29,0.35);">
                                    Gunakan huruf besar, angka, dan simbol untuk keamanan lebih baik.
                                </p>
                            </div>

                            @error('password')
                                <p class="text-[11px] mt-1.5 font-medium" style="color:#9e422c;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div class="mb-7">
                            <label for="password_confirmation"
                                   class="block text-[10px] font-bold tracking-[0.16em] uppercase mb-2"
                                   style="color:rgba(37,29,29,0.4);">
                                Konfirmasi Password
                            </label>
                            <div class="relative">
                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    :type="showConfirm ? 'text' : 'password'"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Ulangi password baru"
                                    x-model="confirm"
                                    class="field-input"
                                    :class="confirm.length > 0 ? (passwordMatch ? 'border-[#655e44] bg-white' : 'is-error') : ''"
                                    style="padding-right:42px;"
                                >
                                <button type="button" class="toggle-eye" @click="showConfirm = !showConfirm">
                                    <svg x-show="!showConfirm" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <svg x-show="showConfirm" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                    </svg>
                                </button>
                            </div>

                            <div x-show="confirm.length > 0" class="mt-1.5 flex items-center gap-1.5">
                                <template x-if="passwordMatch">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="#16a34a" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <template x-if="!passwordMatch">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="#9e422c" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </template>
                                <span class="text-[11px] font-medium"
                                      :style="passwordMatch ? 'color:#16a34a' : 'color:#9e422c'"
                                      x-text="passwordMatch ? 'Password cocok' : 'Password tidak cocok'">
                                </span>
                            </div>

                            @error('password_confirmation')
                                <p class="text-[11px] mt-1.5 font-medium" style="color:#9e422c;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submit --}}
                        <button
                            type="submit"
                            class="btn-industrial w-full py-3.5 rounded-lg text-[13px] font-bold tracking-wide flex items-center justify-center gap-2"
                            style="color:#f2e8c6;"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                            </svg>
                            Simpan Password Baru
                        </button>
                    </form>
                </div>

                {{-- Footer --}}
                <div class="px-7 py-4 flex items-center justify-between" style="background-color:#f4f4ef;">
                    <a href="{{ route('login') }}"
                       class="text-[11px] font-semibold flex items-center gap-1.5"
                       style="color:rgba(37,29,29,0.4);text-decoration:none;">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                        </svg>
                        Kembali ke Login
                    </a>
                    <p class="text-[10px] font-black tracking-[0.1em]" style="color:rgba(37,29,29,0.08);">MR</p>
                </div>
            </div>

        </div>
    </div>

    {{-- ═══════ BOTTOM FOOTER ═══════ --}}
    <div class="px-6 py-4 flex items-center gap-4" style="border-top:1px solid rgba(101,94,68,0.08);">
        <div class="flex-1 h-[1px]" style="background:linear-gradient(90deg,transparent,rgba(101,94,68,0.15));"></div>
        <span class="text-[9px] font-bold tracking-[0.22em] uppercase flex-shrink-0" style="color:rgba(37,29,29,0.2);">
            MAJELIS RENTAL &copy; {{ date('Y') }}
        </span>
        <div class="flex-1 h-[1px]" style="background:linear-gradient(90deg,rgba(101,94,68,0.15),transparent);"></div>
    </div>

</div>
</x-guest-layout>
