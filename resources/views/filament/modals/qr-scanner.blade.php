{{-- window.qrScannerData() di-inject inline di <head> via AdminPanelProvider renderHook --}}

<style>
/* ── Reset html5-qrcode default UI ──────────────────────────────── */
#qr-reader { background: #0f0f0f !important; }
#qr-reader__header_message,
#qr-reader__status_span,
#qr-reader__dashboard { display: none !important; }
#qr-reader video { border-radius: 10px; width: 100% !important; }
#qr-reader__scan_region { background: transparent !important; }
#qr-reader__scan_region img { display: none !important; }

/* ── Modal layout ────────────────────────────────────────────────── */
.qrs-wrap        { display: flex; flex-direction: column; gap: 14px; padding-bottom: 4px; }
.qrs-camera-box  { width: 100%; min-height: 290px; background: #0f0f0f;
                   border-radius: 12px; overflow: hidden;
                   border: 1px solid rgba(255,255,255,0.08); position: relative; }
.qrs-placeholder { position: absolute; inset: 0; display: flex; flex-direction: column;
                   align-items: center; justify-content: center; gap: 10px;
                   color: rgba(255,255,255,0.2); }
.qrs-placeholder svg { width: 60px; height: 60px; }

/* ── Status bar ──────────────────────────────────────────────────── */
.qrs-scanning    { display: flex; align-items: center; justify-content: center; gap: 8px;
                   font-size: 13px; font-weight: 500; color: #0d9488;
                   padding: 4px 0; }
.qrs-spin        { animation: qrs-spin 1s linear infinite; width: 16px; height: 16px; }
@keyframes qrs-spin { to { transform: rotate(360deg); } }

/* ── Success banner ──────────────────────────────────────────────── */
.qrs-found       { display: flex; flex-direction: column; align-items: center; gap: 6px;
                   background: #f0fdf4; border: 1px solid #bbf7d0;
                   border-radius: 12px; padding: 16px; text-align: center; }
.dark .qrs-found { background: rgba(20,83,45,.35); border-color: #166534; }
.qrs-found-title { display: flex; align-items: center; gap: 8px;
                   font-weight: 600; font-size: 13px; color: #15803d; }
.dark .qrs-found-title { color: #86efac; }
.qrs-nomor       { font-family: monospace; font-size: 11px; color: #16a34a; word-break: break-all; }

/* ── Error banner ────────────────────────────────────────────────── */
.qrs-err         { display: flex; gap: 12px; background: #fef2f2;
                   border: 1px solid #fecaca; border-radius: 12px; padding: 14px; }
.dark .qrs-err   { background: rgba(127,29,29,.35); border-color: #991b1b; }
.qrs-err-icon    { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; color: #ef4444; }
.qrs-err-title   { font-size: 13px; font-weight: 700; color: #b91c1c; margin: 0 0 4px; }
.dark .qrs-err-title { color: #fca5a5; }
.qrs-err-body    { font-size: 12px; color: #dc2626; line-height: 1.5; margin: 0; }
.dark .qrs-err-body { color: #f87171; }
.qrs-retry       { margin-top: 6px; font-size: 12px; font-weight: 600; color: #dc2626;
                   background: none; border: none; cursor: pointer; text-decoration: underline;
                   padding: 0; }

/* ── Warning banner (not_secure) ─────────────────────────────────── */
.qrs-warn        { display: flex; gap: 12px; background: #fffbeb;
                   border: 1px solid #fde68a; border-radius: 12px; padding: 14px; }
.dark .qrs-warn  { background: rgba(78,49,5,.4); border-color: #92400e; }
.qrs-warn-title  { font-size: 13px; font-weight: 600; color: #92400e; margin: 0 0 4px; }
.dark .qrs-warn-title { color: #fcd34d; }
.qrs-warn-body   { font-size: 12px; color: #b45309; line-height: 1.5; margin: 0; }
.dark .qrs-warn-body { color: #fbbf24; }
.qrs-warn-body code { background: rgba(0,0,0,.1); padding: 1px 5px; border-radius: 4px;
                       font-family: monospace; }

/* ── Camera selector ─────────────────────────────────────────────── */
.qrs-cam-row     { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
.qrs-cam-label   { font-size: 12px; color: #6b7280; white-space: nowrap; }
.qrs-cam-select  { flex: 1; font-size: 12px; padding: 4px 8px; border-radius: 8px;
                   border: 1px solid #d1d5db; background: #fff; color: #374151;
                   outline: none; }
.dark .qrs-cam-select { background: #1f2937; border-color: #374151; color: #f3f4f6; }

/* ── Divider ─────────────────────────────────────────────────────── */
.qrs-divider     { display: flex; align-items: center; gap: 10px; }
.qrs-divider-line{ flex: 1; border-top: 1px solid #e5e7eb; }
.dark .qrs-divider-line { border-top-color: #374151; }
.qrs-divider-text{ font-size: 11px; color: #9ca3af; white-space: nowrap; }

/* ── Manual input ────────────────────────────────────────────────── */
.qrs-manual-tip  { font-size: 12px; color: #6b7280; text-align: center; margin: 0; }
.qrs-input-row   { display: flex; gap: 8px; }
.qrs-input       { flex: 1; font-size: 13px; padding: 8px 12px; border-radius: 8px;
                   border: 1px solid #d1d5db; background: #fff; color: #111827;
                   outline: none; transition: border-color .15s; }
.qrs-input:focus { border-color: #0d9488; box-shadow: 0 0 0 2px rgba(13,148,136,.2); }
.dark .qrs-input { background: #1f2937; border-color: #374151; color: #f9fafb; }
.qrs-input::placeholder { color: #9ca3af; }
.qrs-btn         { display: flex; align-items: center; gap: 6px; padding: 8px 16px;
                   border-radius: 8px; font-size: 13px; font-weight: 600;
                   background: #0d9488; color: #fff; border: none; cursor: pointer;
                   transition: background .15s; white-space: nowrap; }
.qrs-btn:hover   { background: #0f766e; }
.qrs-btn:disabled{ opacity: .5; cursor: not-allowed; }
.qrs-btn svg     { width: 15px; height: 15px; }

/* ── Tips box ────────────────────────────────────────────────────── */
.qrs-tips        { background: #eff6ff; border: 1px solid #bfdbfe;
                   border-radius: 8px; padding: 10px 12px; }
.dark .qrs-tips  { background: rgba(23,37,84,.4); border-color: rgba(30,64,175,.4); }
.qrs-tips-title  { font-size: 12px; font-weight: 600; color: #1d4ed8; margin: 0 0 4px; }
.dark .qrs-tips-title { color: #93c5fd; }
.qrs-tips ol     { margin: 0; padding-left: 18px; font-size: 12px; color: #2563eb;
                   line-height: 1.7; }
.dark .qrs-tips ol { color: #60a5fa; }
</style>

<div x-data="qrScannerData()"
     @close-modal.window="destroy()" @keydown.escape.window="destroy()"
     class="qrs-wrap">

    {{-- ── Not secure context ──────────────────────────────────── --}}
    <div x-show="errorKey === 'not_secure'" class="qrs-warn">
        <span style="font-size:20px;line-height:1;margin-top:2px">🔒</span>
        <div>
            <p class="qrs-warn-title">Kamera Butuh HTTPS atau Localhost</p>
            <p class="qrs-warn-body">
                Akses admin via <code>http://localhost:8000/admin</code> agar kamera bisa dibuka.
                Atau gunakan input manual 👇
            </p>
        </div>
    </div>

    {{-- ── Camera viewport ─────────────────────────────────────── --}}
    <div x-show="isSecure">
        <div class="qrs-camera-box">
            <div id="qr-reader" style="width:100%;"></div>
            <div class="qrs-placeholder" x-show="!scanning && !found && !errorKey">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                          d="M3 3h6v6H3V3zm0 12h6v6H3v-6zm12-12h6v6h-6V3z"/>
                </svg>
                <span style="font-size:13px;">Memuat kamera…</span>
            </div>
        </div>

        <div class="qrs-cam-row" x-show="cameras.length > 1">
            <span class="qrs-cam-label">Kamera:</span>
            <select class="qrs-cam-select" @change="switchCamera($event.target.value)">
                <template x-for="cam in cameras" :key="cam.id">
                    <option :value="cam.id" :selected="cam.id === activeCam"
                            x-text="cam.label || cam.id"></option>
                </template>
            </select>
        </div>
    </div>

    {{-- ── Scanning active ─────────────────────────────────────── --}}
    <div class="qrs-scanning" x-show="scanning">
        <svg class="qrs-spin" fill="none" viewBox="0 0 24 24">
            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <span>Kamera aktif — arahkan QR Code transaksi ke area kamera…</span>
    </div>

    {{-- ── Found / redirect ────────────────────────────────────── --}}
    <div class="qrs-found" x-show="found">
        <div class="qrs-found-title">
            <svg style="width:18px;height:18px" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            QR terdeteksi! Mengalihkan ke detail transaksi…
        </div>
        <p class="qrs-nomor" x-text="nomor"></p>
        <svg class="qrs-spin" fill="none" viewBox="0 0 24 24" style="color:#16a34a">
            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
    </div>

    {{-- ── Error banner ─────────────────────────────────────────── --}}
    <div class="qrs-err" x-show="errorKey && errorKey !== 'not_secure' && !found">
        <svg class="qrs-err-icon" fill="none" viewBox="0 0 24 24"
             stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="qrs-err-title" x-text="errTitle()"></p>
            <p class="qrs-err-body"  x-text="errBody()"></p>
            <button class="qrs-retry" type="button" @click="retry()"
                    x-show="canRetry()">🔄 Coba Lagi</button>
        </div>
    </div>

    {{-- ── Divider ──────────────────────────────────────────────── --}}
    <div class="qrs-divider">
        <div class="qrs-divider-line"></div>
        <span class="qrs-divider-text">atau input manual</span>
        <div class="qrs-divider-line"></div>
    </div>

    {{-- ── Manual input ─────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:8px;">
        <p class="qrs-manual-tip">💡 Ketik / paste nomor transaksi jika kamera tidak tersedia</p>
        <div class="qrs-input-row">
            <input type="text" class="qrs-input" x-model="manualInput"
                   @keydown.enter="submitManual()"
                   placeholder="Contoh: TRX-YM9BLJXI-260517"/>
            <button type="button" class="qrs-btn"
                    @click="submitManual()"
                    :disabled="!manualInput.trim() || loadingManual">
                <svg x-show="!loadingManual" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <svg x-show="loadingManual" class="qrs-spin" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity:.25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"/>
                    <path style="opacity:.75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span x-text="loadingManual ? 'Mencari…' : 'Cari'"></span>
            </button>
        </div>
    </div>

    {{-- ── Tips izin kamera ─────────────────────────────────────── --}}
    <div class="qrs-tips" x-show="isSecure && !scanning && !found && errorKey !== 'not_secure'">
        <p class="qrs-tips-title">📋 Jika kamera tidak terbuka (Chrome / Edge):</p>
        <ol>
            <li>Klik ikon <strong>kunci 🔒</strong> di address bar</li>
            <li>Pilih <strong>"Izinkan"</strong> pada opsi Kamera</li>
            <li>Refresh (F5) → buka kembali modal</li>
        </ol>
    </div>

</div>
