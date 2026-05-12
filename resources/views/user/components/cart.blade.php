{{-- ── 1. CSRF meta ─────────────────────────────────────────────────── --}}
@once
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endonce

{{-- ── 2. Material Symbols font ─────────────────────────────────────── --}}
@once
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap"
        rel="stylesheet">
@endonce

{{-- Alpine dimuat sekali lewat Vite `resources/js/app.js`. Jangan tambahkan CDN Alpine di sini
     atau di partial lain di halaman yang sama — inisialisasi ganda akan menjalankan `alpine:init`
     lagi dan mengosongkan store keranjang ke nilai Blade (kosong saat first paint). --}}

{{-- ── 3. Alpine Stores (cart & toast) ─────────────────────────────── --}}
@once
    <script>
        document.addEventListener('alpine:init', () => {

            Alpine.store('cart', {
                // Pakai object JSON {} bukan [] agar kunci barang_id konsisten (bukan indeks array).
                items: @json((object) session('cart', [])),
                open: false,
                version: 0,
                _busy: false,
                /** Antrean fetch cart agar tambah/hapus/update tidak jalan paralel. */
                _apiChain: Promise.resolve(),

                get count() {
                    return Object.values(this.items).reduce((s, i) => s + (i.qty || 0), 0);
                },
                get total() {
                    return Object.values(this.items).reduce((s, i) => s + (i.harga || 0) * (i.qty || 0),
                        0);
                },
                get isEmpty() {
                    return this.count === 0;
                },
                get keys() {
                    return Object.keys(this.items);
                },

                formatRupiah(n) {
                    return 'Rp\u00a0' + new Intl.NumberFormat('id-ID').format(n);
                },
                csrf() {
                    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                },

                /** Normalisasi respons server → plain object dengan kunci string barang id. */
                normalizeServerCart(raw) {
                    if (raw === undefined) return null;
                    if (raw === null) return {};
                    if (Array.isArray(raw)) {
                        if (!raw.length) return {};
                        const o = {};
                        for (const row of raw) {
                            if (row && row.barang_id != null) o[String(row.barang_id)] = row;
                        }
                        return o;
                    }
                    if (typeof raw === 'object') {
                        const o = {};
                        for (const k of Object.keys(raw)) o[String(k)] = raw[k];
                        return o;
                    }
                    return {};
                },

                syncItems(newCart) {
                    const next = this.normalizeServerCart(newCart);
                    if (next === null) return;
                    this.items = next;
                    this.version++;
                },

                /** Jalankan satu operasi jaringan cart setelah operasi sebelumnya selesai. */
                api(fn) {
                    const p = this._apiChain.then(() => fn(), () => fn());
                    this._apiChain = p.catch(() => {});
                    return p;
                },

                openPanel() {
                    this.open = true;
                },

                async tambahItem(barangId) {
                    if (barangId === null || barangId === undefined || barangId === '') return false;

                    return this.api(async () => {
                        this._busy = true;
                        try {
                            const res = await fetch(`/keranjang/tambah/${barangId}`, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrf(),
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            const d = await res.json();
                            if (res.ok && d.success) {
                                this.syncItems(d.cart);
                                Alpine.store('toast').flash(d.message, 'success');
                                return true;
                            }
                            Alpine.store('toast').flash(d.message ?? 'Gagal menambahkan.', 'error');
                            return false;
                        } catch (e) {
                            console.error('tambahItem error:', e);
                            Alpine.store('toast').flash('Terjadi kesalahan koneksi.', 'error');
                            return false;
                        } finally {
                            this._busy = false;
                        }
                    });
                },

                async hapus(id) {
                    return this.api(async () => {
                        this._busy = true;
                        try {
                            const res = await fetch(`/keranjang/hapus/${id}`, {
                                method: 'DELETE',
                                credentials: 'same-origin',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrf(),
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            const d = await res.json();
                            if (d.success) {
                                this.syncItems(d.cart);
                                Alpine.store('toast').flash('Item dihapus dari keranjang.', 'info');
                            }
                        } catch {
                            Alpine.store('toast').flash('Gagal menghapus item.', 'error');
                        } finally {
                            this._busy = false;
                        }
                    });
                },

                async update(id, qty) {
                    return this.api(async () => {
                        this._busy = true;
                        try {
                            const res = await fetch(`/keranjang/update/${id}`, {
                                method: 'PATCH',
                                credentials: 'same-origin',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrf(),
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({
                                    qty
                                }),
                            });
                            const d = await res.json();
                            if (d.success) this.syncItems(d.cart);
                            else Alpine.store('toast').flash(d.message, 'error');
                        } catch {
                            Alpine.store('toast').flash('Gagal mengupdate qty.', 'error');
                        } finally {
                            this._busy = false;
                        }
                    });
                },

                async kosongkan() {
                    return this.api(async () => {
                        try {
                            const res = await fetch('/keranjang/kosongkan', {
                                method: 'DELETE',
                                credentials: 'same-origin',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrf(),
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            const d = await res.json();
                            if (d.success) {
                                this.syncItems(d.cart);
                                Alpine.store('toast').flash('Keranjang dikosongkan.', 'info');
                            }
                        } catch {
                            Alpine.store('toast').flash('Gagal mengosongkan keranjang.', 'error');
                        }
                    });
                },
            });

            Alpine.store('toast', {
                show: false,
                message: '',
                type: 'success',
                _timer: null,

                flash(message, type = 'success') {
                    this.message = message;
                    this.type = type;
                    this.show = true;
                    clearTimeout(this._timer);
                    this._timer = setTimeout(() => {
                        this.show = false;
                    }, 3000);
                },
            });
        });
    </script>
@endonce

{{-- TOAST NOTIFICATION --}}
<div x-data x-show="$store.toast.show" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[9999] px-5 py-3 rounded-xl shadow-2xl
           text-sm font-semibold flex items-center gap-3 min-w-[260px] max-w-sm border"
    :class="{
        'bg-[#2d2a1e] border-[#655e44]/50 text-[#F2E8C6]': $store.toast.type === 'success',
        'bg-red-950   border-red-700/50   text-red-200': $store.toast.type === 'error',
        'bg-[#1a1412] border-[#655e44]/30 text-[#F2E8C6]': $store.toast.type === 'info',
    }">

    <span class="material-symbols-outlined text-lg flex-shrink-0"
        :class="{
            'text-green-400': $store.toast.type === 'success',
            'text-red-400': $store.toast.type === 'error',
            'text-[#a8956a]': $store.toast.type === 'info',
        }"
        x-text="{
            success : 'check_circle',
            error   : 'error',
            info    : 'info',
        }[$store.toast.type]">
    </span>

    <span x-text="$store.toast.message" class="flex-1 leading-snug"></span>

    <button @click="$store.toast.show = false" class="opacity-40 hover:opacity-100 transition-opacity flex-shrink-0">
        <span class="material-symbols-outlined text-base">close</span>
    </button>
</div>

{{-- CART SLIDE PANEL — state dari syncItems() (tambah/hapus/update); tidak fetch /keranjang/refresh. --}}
<div x-data>

    {{-- Overlay --}}
    <div x-show="$store.cart.open" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="$store.cart.open = false"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[998]"></div>

    {{-- Panel --}}
    <div x-show="$store.cart.open" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed right-0 top-0 h-full w-full max-w-sm bg-[#1a1412] z-[999] flex flex-col shadow-2xl border-l border-[#655e44]/30">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-[#655e44]/30 flex-shrink-0">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[#F2E8C6] text-xl">shopping_bag</span>
                <h2 class="text-[#F2E8C6] font-bold text-sm uppercase tracking-[0.2em]">Keranjang Sewa</h2>
                <span
                    class="bg-[#4d462e] text-[#F2E8C6] text-[10px] font-black px-2 py-0.5 rounded-full min-w-[20px] text-center"
                    x-text="$store.cart.count"></span>
            </div>
            <button @click="$store.cart.open = false"
                class="text-[#F2E8C6]/40 hover:text-[#F2E8C6] transition-colors p-1 rounded hover:bg-[#655e44]/30">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        {{-- Items List --}}
        <div class="flex-1 overflow-y-auto px-6 py-4">

            {{-- Empty State --}}
            <template x-if="$store.cart.isEmpty">
                <div class="flex flex-col items-center justify-center h-full py-16 text-center">
                    <span class="material-symbols-outlined text-6xl text-[#655e44]/30 mb-4">shopping_cart</span>
                    <p class="text-[#F2E8C6]/50 text-sm uppercase tracking-widest font-semibold">Keranjang kosong</p>
                    <p class="text-[#F2E8C6]/30 text-xs mt-1 mb-6">Tambahkan item dari katalog</p>
                    <a href="{{ route('katalog') }}" @click="$store.cart.open = false"
                        class="px-6 py-2.5 border border-[#655e44]/50 text-[#F2E8C6]/70 text-[10px] uppercase tracking-[0.2em] hover:border-[#655e44] hover:text-[#F2E8C6] hover:bg-[#655e44]/20 transition-all rounded">
                        Lihat Katalog
                    </a>
                </div>
            </template>

            {{-- Cart Items --}}
            <template x-if="!$store.cart.isEmpty">
                <div class="space-y-3" :key="$store.cart.version">
                    <template x-for="barangId in $store.cart.keys" :key="barangId">
                        <div class="bg-[#251D1D] rounded-lg p-4 flex gap-3 border border-[#655e44]/20 group/item">

                            {{-- Foto --}}
                            <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 bg-[#1a1412]">
                                <img :src="$store.cart.items[barangId]?.foto ? `/storage/${$store.cart.items[barangId].foto}` :
                                    '/images/no-image.png'"
                                    :alt="$store.cart.items[barangId]?.nama" class="w-full h-full object-cover">
                            </div>

                            {{-- Detail --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-[#F2E8C6] text-xs font-semibold uppercase tracking-wide leading-tight line-clamp-2 mb-1"
                                    x-text="$store.cart.items[barangId]?.nama"></p>
                                <p class="text-[#a8956a] text-xs font-bold mb-1"
                                    x-text="$store.cart.formatRupiah($store.cart.items[barangId]?.harga ?? 0) + '/hari'">
                                </p>
                                <p class="text-[#655e44] text-[10px] mb-2"
                                    x-text="'Stok: ' + ($store.cart.items[barangId]?.stok ?? 0) + ' unit'"></p>

                                {{-- Qty Control --}}
                                <div class="flex items-center gap-2">
                                    {{-- Tombol Kurang --}}
                                    <button
                                        @click="$store.cart.items[barangId]?.qty > 1
                                            ? $store.cart.update(barangId, $store.cart.items[barangId].qty - 1)
                                            : $store.cart.hapus(barangId)"
                                        class="w-6 h-6 flex items-center justify-center rounded bg-[#655e44]/20 hover:bg-[#655e44] text-[#F2E8C6] transition-colors flex-shrink-0">
                                        <span class="material-symbols-outlined text-sm leading-none">remove</span>
                                    </button>

                                    <span class="text-[#F2E8C6] text-xs font-bold w-5 text-center"
                                        x-text="$store.cart.items[barangId]?.qty ?? 0"></span>

                                    {{-- Tombol Tambah --}}
                                    <button
                                        @click="($store.cart.items[barangId]?.qty ?? 0) < ($store.cart.items[barangId]?.stok ?? 0)
                                            ? $store.cart.tambahItem(barangId)
                                            : Alpine.store('toast').flash(`Stok '${$store.cart.items[barangId]?.nama}' sudah maksimal (${$store.cart.items[barangId]?.stok} unit tersedia).`, 'error')"
                                        class="w-6 h-6 flex items-center justify-center rounded bg-[#655e44]/20 hover:bg-[#655e44] text-[#F2E8C6] transition-colors flex-shrink-0"
                                        :class="($store.cart.items[barangId]?.qty ?? 0) >= ($store.cart.items[barangId]?.stok ??
                                            0) ? 'opacity-40' : ''">
                                        <span class="material-symbols-outlined text-sm leading-none">add</span>
                                    </button>

                                    <span class="ml-auto text-[#a8956a] text-xs font-bold"
                                        x-text="$store.cart.formatRupiah(($store.cart.items[barangId]?.harga ?? 0) * ($store.cart.items[barangId]?.qty ?? 0))">
                                    </span>
                                </div>

                                <div x-show="($store.cart.items[barangId]?.qty ?? 0) >= ($store.cart.items[barangId]?.stok ?? 0)"
                                    class="mt-1.5 inline-flex items-center gap-1 text-[9px] font-bold text-amber-400 bg-amber-900/30 border border-amber-700/30 px-2 py-0.5 rounded-full">
                                    <span class="material-symbols-outlined text-[11px]">warning</span>
                                    Stok Maksimal
                                </div>
                            </div>

                            {{-- Hapus --}}
                            <button @click="$store.cart.hapus(barangId)"
                                class="text-[#F2E8C6]/20 hover:text-red-400 transition-colors flex-shrink-0 self-start mt-0.5 opacity-0 group-hover/item:opacity-100">
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <template x-if="!$store.cart.isEmpty">
            <div class="flex-shrink-0 border-t border-[#655e44]/30 px-6 py-5 space-y-3 bg-[#1a1412]">
                <div class="flex justify-between items-baseline">
                    <span class="text-[#F2E8C6]/50 text-[10px] uppercase tracking-widest">Total Estimasi / Hari</span>
                    <span class="text-[#a8956a] font-extrabold text-xl"
                        x-text="$store.cart.formatRupiah($store.cart.total)"></span>
                </div>
                <p class="text-[#F2E8C6]/25 text-[9px] uppercase tracking-wider">*Belum termasuk durasi & deposit</p>

                <a href="{{ route('checkout.index') }}" @click="$store.cart.open = false"
                    class="w-full block text-center bg-[#4d462e] text-[#F2E8C6] py-3.5 rounded text-[10px] uppercase tracking-[0.2em] font-bold hover:bg-[#655e44] transition-colors">
                    Lanjut ke Checkout
                </a>

                <button @click="$store.cart.kosongkan()"
                    class="w-full text-center text-[#F2E8C6]/25 text-[9px] uppercase tracking-widest hover:text-red-400 transition-colors py-1">
                    Kosongkan Keranjang
                </button>
            </div>
        </template>
    </div>
</div>
