window.qrScannerData = function () {
    return {
        scanning: false, found: false, errorKey: null, nomor: null,
        manualInput: '', loadingManual: false,
        isSecure: window.isSecureContext,
        cameras: [], activeCam: null, _qr: null,

        init() {
            if (!this.isSecure) { this.errorKey = 'not_secure'; return; }
            this._loadLib();
        },

        _loadLib() {
            var self = this;
            if (typeof Html5Qrcode !== 'undefined') {
                setTimeout(function() { self._doStart(); }, 300);
                return;
            }
            if (document.getElementById('h5q')) {
                var t = setInterval(function() {
                    if (typeof Html5Qrcode !== 'undefined') { clearInterval(t); self._doStart(); }
                }, 200);
                return;
            }
            var s = document.createElement('script');
            s.id = 'h5q'; s.src = '/js/html5-qrcode.min.js';
            s.onload = function() { self._doStart(); };
            s.onerror = function() { self.errorKey = 'lib_error'; };
            document.head.appendChild(s);
        },

        _doStart() {
            var self = this;
            if (!navigator.mediaDevices) { self.errorKey = 'api_not_available'; return; }

            self.scanning = true; self.found = false; self.errorKey = null;
            var el = document.getElementById('qr-reader');
            if (el) el.innerHTML = '';
            if (self._qr) { try { self._qr.stop(); } catch(e) {} self._qr = null; }

            self._qr = new Html5Qrcode('qr-reader', { verbose: false });

            // Pakai facingMode:'user' langsung — tidak perlu getCameras dulu
            // sehingga tidak ada double-getUserMedia race condition
            self._qr.start(
                { facingMode: 'user' },
                { fps: 10, qrbox: { width: 230, height: 230 } },
                function(txt) { self._onSuccess(txt); },
                function() {}   // frame tanpa QR — normal, abaikan
            )
            .then(function() {
                // Setelah kamera berhasil start, baru enumerate kamera lain
                Html5Qrcode.getCameras().then(function(d) {
                    if (d && d.length > 1) { self.cameras = d; }
                }).catch(function(){});
            })
            .catch(function(err) {
                self.scanning = false;
                var k = String((err && err.name) || '') + ' ' + String((err && err.message) || err);
                if (/NotAllowed|PermissionDenied|denied|not allowed|permission/i.test(k))
                    self.errorKey = 'permission_denied';
                else if (/NotFound|DevicesNotFound|no_camera/i.test(k))
                    self.errorKey = 'no_camera';
                else if (/NotReadable|TrackStart/i.test(k))
                    self.errorKey = 'camera_busy';
                else
                    self.errorKey = 'generic';
            });
        },


        _onSuccess(text) {
            this.nomor = text.trim(); this.found = true; this.scanning = false;
            if (this._qr) this._qr.stop().catch(function(){});
            this._goto(this.nomor);
        },

        submitManual() {
            var n = this.manualInput.trim(); if (!n) return;
            this.loadingManual = true; this.errorKey = null;
            this.nomor = n; this.found = true; this._goto(n);
        },

        _goto(nomor) {
            var self = this;
            fetch('/admin/api/transaksis/find-by-nomor/' + encodeURIComponent(nomor), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
            .then(function(r) { if (!r.ok) throw 0; return r.json(); })
            .then(function(d) { if (d.url) window.location.href = d.url; else throw 0; })
            .catch(function() { self.found = false; self.loadingManual = false; self.errorKey = 'not_found'; });
        },

        switchCamera(id) {
            var self = this; if (!self._qr) return;
            self._qr.stop().then(function() {
                self.activeCam = id;
                return self._qr.start(id, { fps: 10, qrbox: { width: 230, height: 230 } },
                    function(t) { self._onSuccess(t); }, function() {});
            }).catch(function(){});
        },

        retry() {
            if (this._qr) { this._qr.stop().catch(function(){}); this._qr = null; }
            this.errorKey = null; this.scanning = false; this.found = false;
            this.nomor = null; this.cameras = [];
            if (this.isSecure) this._loadLib();
        },

        destroy() {
            if (this._qr) { this._qr.stop().catch(function(){}); this._qr = null; }
            this.scanning = false;
        },

        errTitle() {
            return ({ not_secure:'🔒 Kamera Butuh HTTPS/Localhost', permission_denied:'🚫 Izin Kamera Ditolak',
                no_camera:'📷 Kamera Tidak Ditemukan', camera_busy:'⚠️ Kamera Dipakai Aplikasi Lain',
                api_not_available:'⚠️ Browser Tidak Mendukung', lib_error:'⚠️ Library Gagal Dimuat',
                not_found:'❌ Transaksi Tidak Ditemukan', generic:'⚠️ Gagal Membuka Kamera'
            })[this.errorKey] || '';
        },
        errBody() {
            return ({ not_secure:'Akses via http://localhost:8000/admin, atau pakai input manual.',
                permission_denied:'Klik 🔒 di address bar → Izinkan Kamera → refresh (F5) → buka modal lagi.',
                no_camera:'Tidak ada kamera. Pastikan webcam aktif. Pakai input manual.',
                camera_busy:'Kamera dipakai aplikasi lain. Tutup lalu klik Coba Lagi.',
                api_not_available:'Gunakan Chrome atau Edge terbaru.',
                lib_error:'Cek koneksi internet. Pakai input manual.',
                not_found:'Nomor transaksi tidak ditemukan.',
                generic:'Terjadi kesalahan. Coba lagi atau pakai input manual.'
            })[this.errorKey] || '';
        },
        canRetry() {
            return ['permission_denied','camera_busy','generic','lib_error'].indexOf(this.errorKey) >= 0;
        },
    };
};
