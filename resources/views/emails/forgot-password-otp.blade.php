<x-guest-layout>
{{--
    forgot-password-otp.blade.php
    Design System: Industrial Precision — "The Overbuilt Utility"
--}}

<style>
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: #f4f4ef; }
    ::-webkit-scrollbar-thumb { background: #655e44; border-radius: 2px; }

    .otp-slot:focus {
        outline: none;
        border-color: #655e44 !important;
        background-color: #ffffff !important;
        box-shadow: 0 0 0 3px rgba(101,94,68,0.12);
    }
    .otp-slot.has-value {
        border-color: rgba(101,94,68,0.5);
        background-color: #ffffff;
    }
    .otp-slot.is-error {
        border-color: #9e422c !important;
        background-color: #fdf2ef !important;
    }

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
</style>

<div
    class="min-h-screen flex flex-col"
    style="background-color:#f4f4ef;"
    x-data="{
        digits: ['','','','','',''],
        cooldown: 0,
        timer: null,

        get otp() { return this.digits.join('') },
        get otpFull() { return this.otp.length === 6 },

        handleInput(i, e) {
            const v = e.target.value.replace(/\D/g,'');
            if (!v) { this.digits[i] = ''; return; }
            this.digits[i] = v.slice(-1);
            if (i < 5) this.$nextTick(() => document.getElementById('d'+(i+1))?.focus());
        },
        handleKey(i, e) {
            if (e.key === 'Backspace' && !this.digits[i] && i > 0)
                document.getElementById('d'+(i-1))?.focus();
            if (e.key === 'ArrowLeft'  && i > 0) document.getElementById('d'+(i-1))?.focus();
            if (e.key === 'ArrowRight' && i < 5) document.getElementById('d'+(i+1))?.focus();
        },
        handlePaste(e) {
            const s = e.clipboardData.getData('text').replace(/\D/g,'').slice(0,6);
            for (let i=0;i<6;i++) this.digits[i] = s[i] || '';
            this.$nextTick(() => document.getElementById('d'+Math.min(s.length,5))?.focus());
        },
        startCooldown(sec) {
            this.cooldown = sec;
            clearInterval(this.timer);
            this.timer = setInterval(() => {
                if (--this.cooldown <= 0) { this.cooldown = 0; clearInterval(this.timer); }
            }, 1000);
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
                OTP Verification
            </span>
        </div>
    </div>

    {{-- ═══════ CONTENT ═══════ --}}
    <div class="flex-1 flex items-center justify-center px-4 py-10">
        <div class="w-full fade-up" style="max-width:440px;">

            {{-- Flash: OTP terkirim --}}
            @if (session('status') === 'otp-sent')
                <div class="mb-4 px-4 py-3 rounded-lg flex items-start gap-3"
                     style="background-color:#f0fdf4;border-left:3px solid #16a34a;">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="#16a34a" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <p class="text-[13px] font-medium" style="color:#15803d;">
                        Kode OTP baru telah dikirim ke email Anda.
                    </p>
                </div>
            @endif

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
                                Majelis Rental — Verifikasi OTP
                            </span>
                        </div>
                        <h1 class="text-[22px] font-black tracking-tight leading-tight" style="color:#f2e8c6;">
                            Masukkan Kode OTP
                        </h1>
                        <p class="text-[12px] mt-1.5" style="color:rgba(242,232,198,0.4);">
                            Kode 6 digit dikirim ke
                            <span style="color:rgba(242,232,198,0.75);">{{ $email }}</span>
                        </p>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-7 py-8">

                    {{-- Error OTP --}}
                    @error('otp')
                        <div class="mb-5 px-4 py-3 rounded-lg flex items-start gap-2.5"
                             style="background-color:#fdf2ef;border-left:3px solid #9e422c;">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                 stroke="#9e422c" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                            <p class="text-[12px] font-medium" style="color:#9e422c;">{{ $message }}</p>
                        </div>
                    @enderror

                    {{-- OTP Form --}}
                    <form method="POST" action="{{ route('password.otp.verify') }}">
                        @csrf
                        <input type="hidden" name="otp" :value="otp">

                        <div class="mb-6">
                            <p class="text-[10px] font-bold tracking-[0.16em] uppercase mb-4"
                               style="color:rgba(37,29,29,0.3);">
                                Kode Verifikasi
                            </p>

                            <div class="flex gap-2 justify-center" @paste.prevent="handlePaste($event)">
                                @for ($i = 0; $i < 6; $i++)
                                    <input
                                        id="d{{ $i }}"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="1"
                                        autocomplete="off"
                                        spellcheck="false"
                                        x-model="digits[{{ $i }}]"
                                        @input="handleInput({{ $i }}, $event)"
                                        @keydown="handleKey({{ $i }}, $event)"
                                        :class="{
                                            'has-value': digits[{{ $i }}] !== '',
                                            'is-error':  {{ $errors->has('otp') ? 'true' : 'false' }}
                                        }"
                                        class="otp-slot w-11 h-14 text-center text-[22px] font-black rounded-lg border-[1.5px] transition-all duration-150 select-none"
                                        style="background-color:#f4f4ef;border-color:rgba(101,94,68,0.2);color:#251D1D;font-variant-numeric:tabular-nums;caret-color:#655e44;"
                                    >
                                    @if ($i === 2)
                                        <div class="flex items-center px-1">
                                            <div class="w-2.5 h-[2px] rounded-full" style="background:rgba(101,94,68,0.2);"></div>
                                        </div>
                                    @endif
                                @endfor
                            </div>

                            <p class="text-center text-[11px] mt-3" style="color:rgba(37,29,29,0.35);">
                                Tempel
                                (<kbd class="px-1.5 py-0.5 rounded text-[10px]"
                                     style="background:#f4f4ef;color:rgba(37,29,29,0.5);border:1px solid rgba(101,94,68,0.15);">Ctrl+V</kbd>)
                                kode langsung dari email
                            </p>
                        </div>

                        {{-- Submit --}}
                        <button
                            type="submit"
                            class="btn-industrial w-full py-3.5 rounded-lg text-[13px] font-bold tracking-wide"
                            style="color:#f2e8c6;"
                            :disabled="!otpFull"
                        >
                            <span x-show="otpFull" class="flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Verifikasi &amp; Lanjutkan
                            </span>
                            <span x-show="!otpFull" style="color:rgba(242,232,198,0.45);">
                                Masukkan 6 digit kode
                            </span>
                        </button>
                    </form>

                    {{-- Resend --}}
                    <div class="text-center mt-6 pt-6" style="border-top:1px solid rgba(101,94,68,0.1);">
                        <p class="text-[12px] mb-2" style="color:rgba(37,29,29,0.4);">Belum menerima kode?</p>
                        <form method="POST" action="{{ route('password.otp.resend') }}" class="inline"
                              x-on:submit="startCooldown(60)">
                            @csrf
                            <button
                                type="submit"
                                class="text-[12px] font-bold underline underline-offset-2 border-none bg-transparent cursor-pointer transition-colors duration-150"
                                style="color:#655e44;font-family:inherit;"
                                :disabled="cooldown > 0"
                                :class="cooldown > 0 ? 'opacity-40 cursor-not-allowed' : ''"
                            >
                                <span x-show="cooldown <= 0">Kirim Ulang Kode OTP</span>
                                <span x-show="cooldown > 0" style="color:rgba(37,29,29,0.4);">
                                    Kirim ulang dalam <span x-text="cooldown"></span>s
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-7 py-4 flex items-center justify-between" style="background-color:#f4f4ef;">
                    <a href="{{ route('password.request') }}"
                       class="text-[11px] font-semibold flex items-center gap-1.5"
                       style="color:rgba(37,29,29,0.4);text-decoration:none;">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                        </svg>
                        Ganti Email
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
