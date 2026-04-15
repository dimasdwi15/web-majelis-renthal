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

{{-- ── 3. Alpine JS ─────────────────────────────────────────────────── --}}
<script>
    window.Alpine = window.Alpine || {};
</script>
@once
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endonce

{{-- ── 4. Alpine Stores (cart & toast) ─────────────────────────────── --}}
@once
    <script>
        document.addEventListener('alpine:init', () => {

            // ─── STORE: CART ───────────────────────────────────────────────
            Alpine.store('cart', {
                items: @json(session('cart', [])),
                open: false,

                get count() {
                    return Object.values(this.items).reduce((s, i) => s + i.qty, 0);
                },
                get total() {
                    return Object.values(this.items).reduce((s, i) => s + i.harga * i.qty, 0);
                },
                get isEmpty() {
                    return this.count === 0;
                },

                formatRupiah(n) {
                    return 'Rp\u00a0' + new Intl.NumberFormat('id-ID').format(n);
                },
                csrf() {
                    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                },

                /**
                 * Refresh data cart dari server — memastikan nama, harga,
                 * stok selalu sinkron dengan perubahan terbaru di database.
                 * Dipanggil otomatis setiap kali panel dibuka (via x-effect).
                 */
                async refresh() {
                    try {
                        const res = await fetch('/keranjang/refresh', {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                            },
                        });
                        const d = await res.json();
                        if (d.success) {
                            this.items = d.cart;
                        }
                    } catch (e) {
                        console.warn('Cart refresh failed:', e);
                    }
                },

                async tambahItem(barangId) {
                    try {
                        const res = await fetch(`/keranjang/tambah/${barangId}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrf(),
                                'Accept': 'application/json',
                            },
                        });
                        const d = await res.json();
                        if (res.ok && d.success) {
                            this.items = d.cart;
                            Alpine.store('toast').flash(d.message, 'success');
                            return true;
                        } else {
                            Alpine.store('toast').flash(d.message ?? 'Gagal menambahkan.', 'error');
                            return false;
                        }
                    } catch {
                        Alpine.store('toast').flash('Terjadi kesalahan koneksi.', 'error');
                        return false;
                    }
                },

                async hapus(id) {
                    try {
                        const res = await fetch(`/keranjang/hapus/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': this.csrf(),
                                'Accept': 'application/json'
                            },
                        });
                        const d = await res.json();
                        if (d.success) {
                            this.items = d.cart;
                            Alpine.store('toast').flash('Item dihapus dari keranjang.', 'info');
                        }
                    } catch {
                        Alpine.store('toast').flash('Gagal menghapus item.', 'error');
                    }
                },

                async update(id, qty) {
                    try {
                        const res = await fetch(`/keranjang/update/${id}`, {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': this.csrf(),
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ qty }),
                        });
                        const d = await res.json();
                        if (d.success) this.items = d.cart;
                        else Alpine.store('toast').flash(d.message, 'error');
                    } catch {
                        Alpine.store('toast').flash('Gagal mengupdate qty.', 'error');
                    }
                },

                async kosongkan() {
                    try {
                        const res = await fetch('/keranjang/kosongkan', {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': this.csrf(),
                                'Accept': 'application/json'
                            },
                        });
                        const d = await res.json();
                        if (d.success) {
                            this.items = {};
                            Alpine.store('toast').flash('Keranjang dikosongkan.', 'info');
                        }
                    } catch {
                        Alpine.store('toast').flash('Gagal mengosongkan keranjang.', 'error');
                    }
                },
            });

            // ─── STORE: TOAST ──────────────────────────────────────────────
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

{{-- ════════════════════════════════════════════════════════════════════
     TOAST NOTIFICATION
     ════════════════════════════════════════════════════════════════ --}}
<div x-data x-show="$store.toast.show" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[9999] px-5 py-3 rounded-xl shadow-2xl
           text-sm font-semibold flex items-center gap-3 min-w-[260px] max-w-sm border"
    :class="{
        'bg-[#2d2a1e] border-[#655e44]/50 text-[#F2E8C6]': $store.toast.type === 'success',
        'bg-red-950   border-red-700/50   text-red-200':   $store.toast.type === 'error',
        'bg-[#1a1412] border-[#655e44]/30 text-[#F2E8C6]': $store.toast.type === 'info',
    }">

    <span class="material-symbols-outlined text-lg flex-shrink-0"
        :class="{
            'text-green-400': $store.toast.type === 'success',
            'text-red-400':   $store.toast.type === 'error',
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

{{-- ════════════════════════════════════════════════════════════════════
     CART SLIDE PANEL
     x-effect: setiap kali open berubah jadi true, refresh data dari
     server agar harga/nama/stok selalu up-to-date vs perubahan admin.
     ════════════════════════════════════════════════════════════════ --}}
<div x-data x-effect="if ($store.cart.open) $store.cart.refresh()">

    {{-- Overlay --}}
    <div x-show="$store.cart.open" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="$store.cart.open = false"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[998]">
    </div>

    {{-- Panel --}}
    <div x-show="$store.cart.open" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed right-0 top-0 h-full w-full max-w-sm bg-[#1a1412] z-[999]
               flex flex-col shadow-2xl border-l border-[#655e44]/30">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-[#655e44]/30 flex-shrink-0">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[#F2E8C6] text-xl">shopping_bag</span>
                <h2 class="text-[#F2E8C6] font-bold text-sm uppercase tracking-[0.2em]">
                    Keranjang Sewa
                </h2>
                <span
                    class="bg-[#4d462e] text-[#F2E8C6] text-[10px] font-black px-2 py-0.5 rounded-full min-w-[20px] text-center"
                    x-text="$store.cart.count">
                </span>
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
                    <p class="text-[#F2E8C6]/50 text-sm uppercase tracking-widest font-semibold">
                        Keranjang kosong
                    </p>
                    <p class="text-[#F2E8C6]/30 text-xs mt-1 mb-6">
                        Tambahkan item dari katalog
                    </p>
                    <a href="{{ route('katalog') }}"
                        @click="$store.cart.open = false"
                        class="px-6 py-2.5 border border-[#655e44]/50 text-[#F2E8C6]/70 text-[10px]
                               uppercase tracking-[0.2em] hover:border-[#655e44] hover:text-[#F2E8C6]
                               hover:bg-[#655e44]/20 transition-all rounded">
                        Lihat Katalog
                    </a>
                </div>
            </template>

            {{-- Cart Items --}}
            <template x-if="!$store.cart.isEmpty">
                <div class="space-y-3">
                    <template x-for="(item, id) in $store.cart.items" :key="id">
                        <div class="bg-[#251D1D] rounded-lg p-4 flex gap-3 border border-[#655e44]/20 group/item">

                            {{-- Foto --}}
                            <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 bg-[#1a1412]">
                                <img :src="item.foto ? `/storage/${item.foto}` : '/images/no-image.png'"
                                    :alt="item.nama" class="w-full h-full object-cover">
                            </div>

                            {{-- Detail --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-[#F2E8C6] text-xs font-semibold uppercase tracking-wide
                                          leading-tight line-clamp-2 mb-1"
                                    x-text="item.nama">
                                </p>
                                <p class="text-[#a8956a] text-xs font-bold mb-1"
                                    x-text="$store.cart.formatRupiah(item.harga) + '/hari'">
                                </p>

                                {{-- Info stok tersisa --}}
                                <p class="text-[#655e44] text-[10px] mb-2"
                                    x-text="'Stok: ' + item.stok + ' unit'">
                                </p>

                                {{-- Qty Control --}}
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="item.qty > 1
                                            ? $store.cart.update(id, item.qty - 1)
                                            : $store.cart.hapus(id)"
                                        class="w-6 h-6 flex items-center justify-center rounded
                                               bg-[#655e44]/20 hover:bg-[#655e44] text-[#F2E8C6]
                                               transition-colors flex-shrink-0">
                                        <span class="material-symbols-outlined text-sm leading-none">remove</span>
                                    </button>

                                    <span class="text-[#F2E8C6] text-xs font-bold w-5 text-center" x-text="item.qty">
                                    </span>

                                    {{--
                                        FIX: Tombol tambah di cart panel sekarang memeriksa stok
                                        dan menampilkan notifikasi informatif dengan nama barang
                                        dan sisa stok jika sudah mencapai maksimal.
                                    --}}
                                    <button
                                        @click="item.qty < item.stok
                                            ? $store.cart.update(id, item.qty + 1)
                                            : Alpine.store('toast').flash(
                                                `Stok \"${item.nama}\" sudah maksimal (${item.stok} unit tersedia).`,
                                                'error'
                                              )"
                                        class="w-6 h-6 flex items-center justify-center rounded
                                               bg-[#655e44]/20 hover:bg-[#655e44] text-[#F2E8C6]
                                               transition-colors flex-shrink-0"
                                        :class="item.qty >= item.stok ? 'opacity-40' : ''">
                                        <span class="material-symbols-outlined text-sm leading-none">add</span>
                                    </button>

                                    {{-- Subtotal --}}
                                    <span class="ml-auto text-[#a8956a] text-xs font-bold"
                                        x-text="$store.cart.formatRupiah(item.harga * item.qty)">
                                    </span>
                                </div>

                                {{-- Badge stok maksimal --}}
                                <div x-show="item.qty >= item.stok"
                                    class="mt-1.5 inline-flex items-center gap-1 text-[9px] font-bold text-amber-400 bg-amber-900/30 border border-amber-700/30 px-2 py-0.5 rounded-full">
                                    <span class="material-symbols-outlined text-[11px]">warning</span>
                                    Stok Maksimal
                                </div>
                            </div>

                            {{-- Hapus --}}
                            <button @click="$store.cart.hapus(id)"
                                class="text-[#F2E8C6]/20 hover:text-red-400 transition-colors
                                       flex-shrink-0 self-start mt-0.5 opacity-0 group-hover/item:opacity-100">
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Footer Panel --}}
        <template x-if="!$store.cart.isEmpty">
            <div class="flex-shrink-0 border-t border-[#655e44]/30 px-6 py-5 space-y-3 bg-[#1a1412]">

                {{-- Total --}}
                <div class="flex justify-between items-baseline">
                    <span class="text-[#F2E8C6]/50 text-[10px] uppercase tracking-widest">
                        Total Estimasi / Hari
                    </span>
                    <span class="text-[#a8956a] font-extrabold text-xl"
                        x-text="$store.cart.formatRupiah($store.cart.total)">
                    </span>
                </div>
                <p class="text-[#F2E8C6]/25 text-[9px] uppercase tracking-wider">
                    *Belum termasuk durasi & deposit
                </p>

                <a href="{{ route('checkout.index') }}"
                    class="w-full block text-center bg-[#4d462e] text-[#F2E8C6] py-3.5 rounded
                    text-[10px] uppercase tracking-[0.2em] font-bold
                    hover:bg-[#655e44] transition-colors">
                    Lanjut ke Checkout
                </a>

                <button @click="$store.cart.kosongkan()"
                    class="w-full text-center text-[#F2E8C6]/25 text-[9px] uppercase tracking-widest
                           hover:text-red-400 transition-colors py-1">
                    Kosongkan Keranjang
                </button>
            </div>
        </template>

    </div>
</div>
