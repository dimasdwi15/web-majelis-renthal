function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function handleCartResponse(res) {
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const msg = data.message || data.error || 'Terjadi kesalahan.';
        throw new Error(typeof msg === 'string' ? msg : 'Terjadi kesalahan.');
    }
    return data;
}

document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
        message: '',
        type: 'success',
        visible: false,
        _timeout: null,
        flash(message, type = 'success') {
            this.message = message;
            this.type = type;
            this.visible = true;
            clearTimeout(this._timeout);
            this._timeout = setTimeout(() => {
                this.visible = false;
            }, 3200);
        },
    });

    const mergeCartFromServer = (raw) => {
        if (!raw || typeof raw !== 'object' || Array.isArray(raw)) {
            return;
        }
        Alpine.store('cart').syncItems(raw);
    };

    Alpine.store('cart', {
        items: {},
        version: 0,
        panelOpen: false,
        loading: false,

        get isEmpty() {
            return Object.keys(this.items).length === 0;
        },
        get count() {
            return Object.values(this.items).reduce((s, i) => s + (Number(i.qty) || 0), 0);
        },
        get subtotalPerHari() {
            return Object.values(this.items).reduce(
                (s, i) => s + Number(i.harga) * Number(i.qty),
                0,
            );
        },

        syncItems(raw) {
            const next = {};
            if (raw && typeof raw === 'object') {
                for (const [k, v] of Object.entries(raw)) {
                    if (!v || typeof v !== 'object') {
                        continue;
                    }
                    const id = String(k);
                    next[id] = {
                        id,
                        nama: v.nama ?? '',
                        harga: Number(v.harga) || 0,
                        stok: Number(v.stok) || 0,
                        qty: Math.max(1, Number(v.qty) || 1),
                        foto: v.foto ?? null,
                    };
                }
            }
            this.items = next;
            this.version++;
        },

        openPanel() {
            this.panelOpen = true;
            document.body.classList.add('overflow-hidden');
        },
        closePanel() {
            this.panelOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        /**
         * Tarik ulang dari server (mis. multi-tab). Gabung aman: tidak menimpa jika response kosong
         * sementara klien punya item (hindari flash hilang saat race).
         */
        async bootstrapFromServer() {
            if (this.loading) {
                return;
            }
            this.loading = true;
            try {
                const res = await fetch('/keranjang/sync', {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await handleCartResponse(res);
                const serverCart = data.cart;
                const serverKeys = serverCart && typeof serverCart === 'object'
                    ? Object.keys(serverCart).length
                    : 0;
                const clientKeys = Object.keys(this.items).length;
                if (serverKeys === 0 && clientKeys > 0) {
                    return;
                }
                this.syncItems(serverCart);
            } catch (e) {
                console.warn(e);
            } finally {
                this.loading = false;
            }
        },

        async tambahItem(barangId) {
            try {
                const res = await fetch(`/keranjang/tambah/${barangId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({}),
                });
                const data = await handleCartResponse(res);
                this.syncItems(data.cart);
                return true;
            } catch (e) {
                Alpine.store('toast').flash(e.message, 'error');
                return false;
            }
        },

        async updateQty(barangId, qty) {
            try {
                const res = await fetch(`/keranjang/update/${barangId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ qty }),
                });
                const data = await handleCartResponse(res);
                this.syncItems(data.cart);
            } catch (e) {
                Alpine.store('toast').flash(e.message, 'error');
            }
        },

        async hapus(barangId) {
            try {
                const res = await fetch(`/keranjang/hapus/${barangId}`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await handleCartResponse(res);
                this.syncItems(data.cart);
            } catch (e) {
                Alpine.store('toast').flash(e.message, 'error');
            }
        },

        async increment(barangId) {
            const item = this.items[String(barangId)];
            if (!item) {
                return;
            }
            if (item.qty >= item.stok) {
                Alpine.store('toast').flash(
                    `Stok maksimal ${item.stok} untuk "${item.nama}".`,
                    'error',
                );
                return;
            }
            await this.updateQty(barangId, item.qty + 1);
        },

        async decrement(barangId) {
            const item = this.items[String(barangId)];
            if (!item) {
                return;
            }
            if (item.qty <= 1) {
                await this.hapus(barangId);
                return;
            }
            await this.updateQty(barangId, item.qty - 1);
        },

        /**
         * Kompatibel dengan halaman checkout yang memanggil update(id, qty).
         */
        async update(id, qty) {
            await this.updateQty(id, qty);
        },
    });

    mergeCartFromServer(window.__INITIAL_CART__);
});
