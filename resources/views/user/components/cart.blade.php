{{-- Hidrasi awal dari session (hindari race GET /keranjang/sync vs POST tambah) --}}
<script>
    window.__INITIAL_CART__ = @json(\App\Support\CartSessionHelper::getRefreshedCart());
</script>

{{-- Panel keranjang (drawer kanan) + toast --}}
<div x-data
    @keydown.escape.window="$store.cart.panelOpen && $store.cart.closePanel()">

    {{-- Backdrop --}}
    <div x-show="$store.cart.panelOpen" x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="$store.cart.closePanel()"
        class="fixed inset-0 z-[60] bg-[#251D1D]/70 backdrop-blur-sm"
        style="display: none;"></div>

    {{-- Drawer --}}
    <aside x-show="$store.cart.panelOpen" x-cloak
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed top-0 right-0 z-[70] h-full w-full max-w-md flex flex-col shadow-2xl border-l border-[#655e44]/25"
        style="display: none; background: linear-gradient(165deg, #1e1714 0%, #251D1D 45%, #1a1512 100%);">

        {{-- Header --}}
        <div
            class="flex-shrink-0 flex items-start justify-between gap-3 px-5 pt-5 pb-4 border-b border-[#655e44]/20">
            <div>
                <p class="text-[10px] font-black tracking-[0.28em] uppercase text-[#a8956a] mb-1">Keranjang Sewa</p>
                <h2 class="font-inter text-lg font-black text-[#F2E8C6] tracking-tight leading-none">Gear Terpilih</h2>
                <p class="text-[11px] text-[#F2E8C6]/45 mt-2 leading-snug max-w-[240px]">
                    Sesuaikan jumlah unit sebelum lanjut ke checkout.
                </p>
            </div>
            <button type="button" @click="$store.cart.closePanel()"
                class="flex h-10 w-10 items-center justify-center rounded-xl text-[#F2E8C6]/60 hover:text-[#F2E8C6] hover:bg-[#655e44]/30 transition-colors border border-transparent hover:border-[#655e44]/35"
                aria-label="Tutup keranjang">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        {{-- List --}}
        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3">
            <template x-if="$store.cart.isEmpty">
                <div class="flex flex-col items-center justify-center text-center py-16 px-6">
                    <div
                        class="w-16 h-16 rounded-2xl bg-[#655e44]/15 border border-[#655e44]/25 flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-[#a8956a] text-3xl">shopping_bag</span>
                    </div>
                    <p class="text-sm font-semibold text-[#F2E8C6]/90 mb-1">Belum ada barang</p>
                    <p class="text-xs text-[#F2E8C6]/40 mb-6 leading-relaxed">Tambahkan gear dari katalog — stok &amp;
                        harga otomatis tersinkron.</p>
                    <a href="{{ route('katalog') }}" @click="$store.cart.closePanel()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#a8956a] text-[#251D1D] text-[10px] font-black uppercase tracking-widest hover:bg-[#c4b08c] transition-colors shadow-lg shadow-black/20">
                        <span class="material-symbols-outlined text-base">inventory_2</span>
                        Jelajahi Katalog
                    </a>
                </div>
            </template>

            <template x-for="(item, id) in $store.cart.items" :key="id + '-' + $store.cart.version">
                <article
                    class="group rounded-2xl border border-[#655e44]/20 bg-[#1a1412]/80 p-3.5 flex gap-3 shadow-sm hover:border-[#a8956a]/35 transition-colors">
                    <div
                        class="w-[72px] h-[72px] rounded-xl overflow-hidden bg-[#0f0c0b] border border-[#655e44]/15 flex-shrink-0">
                        <img :src="item.foto ? '/storage/' + item.foto : '/images/no-image.png'" :alt="item.nama"
                            class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0 flex flex-col">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-[12px] font-bold text-[#F2E8C6] leading-snug line-clamp-2 uppercase tracking-wide"
                                x-text="item.nama"></h3>
                            <button type="button" @click="$store.cart.hapus(id)"
                                class="flex-shrink-0 p-1 rounded-lg text-[#F2E8C6]/35 hover:text-red-400 hover:bg-red-500/10 transition-colors"
                                title="Hapus">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                        <p class="text-[10px] text-[#a8956a] font-semibold mt-0.5">
                            <span
                                x-text="'Rp\u00a0' + new Intl.NumberFormat('id-ID').format(Math.round(item.harga))"></span>
                            <span class="text-[#F2E8C6]/35 font-normal">/hari</span>
                        </p>
                        <div class="flex items-center justify-between mt-auto pt-2">
                            <div class="inline-flex items-center rounded-xl border border-[#655e44]/35 bg-[#251D1D]/60 p-0.5">
                                <button type="button" @click="$store.cart.decrement(id)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-[#F2E8C6]/80 hover:bg-[#655e44]/40 transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">remove</span>
                                </button>
                                <span class="min-w-[2rem] text-center text-xs font-black text-[#F2E8C6]"
                                    x-text="item.qty"></span>
                                <button type="button" @click="$store.cart.increment(id)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-[#F2E8C6]/80 hover:bg-[#655e44]/40 transition-colors disabled:opacity-35"
                                    :disabled="item.qty >= item.stok">
                                    <span class="material-symbols-outlined text-[18px]">add</span>
                                </button>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] uppercase tracking-wider text-[#F2E8C6]/35">Subtotal /hari</p>
                                <p class="text-[12px] font-black text-[#F2E8C6]"
                                    x-text="'Rp\u00a0' + new Intl.NumberFormat('id-ID').format(Math.round(item.harga * item.qty))">
                                </p>
                            </div>
                        </div>
                        <p class="text-[9px] text-[#F2E8C6]/30 mt-1" x-show="item.qty >= item.stok">
                            Stok tersisa: <span x-text="item.stok"></span>
                        </p>
                    </div>
                </article>
            </template>
        </div>

        {{-- Footer CTA --}}
        <div
            class="flex-shrink-0 border-t border-[#655e44]/20 px-5 py-4 space-y-3 bg-[#1a1412]/95 backdrop-blur-md">
            <div class="flex items-center justify-between text-[11px]">
                <span class="text-[#F2E8C6]/45 uppercase tracking-widest font-bold">Total rate / hari</span>
                <span class="text-[#F2E8C6] font-black text-sm"
                    x-text="'Rp\u00a0' + new Intl.NumberFormat('id-ID').format(Math.round($store.cart.subtotalPerHari))"></span>
            </div>
            <a href="{{ route('checkout.index') }}" @click="$store.cart.closePanel()"
                class="flex w-full items-center justify-center gap-2 py-3.5 rounded-xl font-inter font-black text-[11px] uppercase tracking-[0.18em] transition-all duration-200 shadow-lg border border-[#a8956a]/40"
                :class="$store.cart.isEmpty ? 'pointer-events-none opacity-40 bg-[#655e44]/20 text-[#F2E8C6]/50' :
                    'bg-gradient-to-r from-[#a8956a] to-[#8a7a56] text-[#251D1D] hover:brightness-110 hover:-translate-y-0.5'">
                <span class="material-symbols-outlined text-[18px]">payments</span>
                Lanjut Checkout
            </a>
            <a href="{{ route('katalog') }}" @click="$store.cart.closePanel()"
                class="block text-center text-[10px] font-bold uppercase tracking-widest text-[#655e44] hover:text-[#a8956a] transition-colors">
                Tambah barang lain
            </a>
        </div>
    </aside>

    {{-- Toast --}}
    <div x-show="$store.toast.visible" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-6 left-1/2 z-[80] -translate-x-1/2 max-w-sm w-[calc(100%-2rem)] pointer-events-none"
        style="display: none;">
        <div class="pointer-events-auto rounded-2xl px-4 py-3 shadow-xl border flex items-start gap-3"
            :class="$store.toast.type === 'error'
                ? 'bg-[#2c1515] border-red-500/30 text-red-100'
                : 'bg-[#1e1714] border-[#655e44]/40 text-[#F2E8C6]'">
            <span class="material-symbols-outlined text-xl flex-shrink-0"
                x-text="$store.toast.type === 'error' ? 'error' : 'check_circle'"></span>
            <p class="text-xs font-semibold leading-relaxed" x-text="$store.toast.message"></p>
        </div>
    </div>
</div>
