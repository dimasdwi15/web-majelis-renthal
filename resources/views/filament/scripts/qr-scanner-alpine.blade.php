<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" defer></script>

<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('qrScanner', function () {
        return {
            scanning:      false,
            found:         false,
            error:         null,
            nomor:         null,
            manualInput:   '',
            loadingManual: false,
            isSecure:      false,
            cameras:       [],
            activeCam:     null,
            qr:            null,

            init() {
                var self = this;
                self.isSecure = window.isSecureContext;

                if (!self.isSecure) {
                    self.error = 'not_secure';
                    return;
                }

                // Tunggu sedikit agar DOM modal sudah fully-rendered
                setTimeout(function () { self.startCamera(); }, 500);
            },

            startCamera() {
                var self = this;

                if (typeof Html5Qrcode === 'undefined') {
                    self.error = 'library_not_ready';
                    return;
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    self.error = 'api_not_available';
                    return;
                }

                self.scanning = true;
                self.found    = false;
                self.error    = null;
                self.nomor    = null;

                var el = document.getElementById('qr-reader');
                if (el) el.innerHTML = '';

                // Minta izin kamera eksplisit dulu
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(function (stream) {
                        stream.getTracks().forEach(function (t) { t.stop(); });

                        self.qr = new Html5Qrcode('qr-reader');
                        return Html5Qrcode.getCameras();
                    })
                    .then(function (devices) {
                        if (!devices || devices.length === 0) {
                            throw Object.assign(new Error('no_camera'), { name: 'NotFoundError' });
                        }

                        self.cameras = devices;

                        // Laptop: pilih webcam/front; hindari kamera belakang
                        var chosen = devices.find(function (d) {
                            return /front|user|webcam|built.?in|integrated/i.test(d.label);
                        });
                        if (!chosen) {
                            chosen = devices.find(function (d) {
                                return !/back|rear|environment/i.test(d.label);
                            }) || devices[0];
                        }

                        self.activeCam = chosen.id;

                        return self.qr.start(
                            chosen.id,
                            {
                                fps: 10,
                                qrbox: function (w, h) {
                                    var s = Math.min(w, h, 260);
                                    return { width: s, height: s };
                                },
                            },
                            function (text) { self.onScanSuccess(text); },
                            function () {}
                        );
                    })
                    .catch(function (err) {
                        self.scanning = false;
                        var name = (err && err.name) ? err.name : '';
                        var msg  = (err && err.message) ? err.message : String(err);

                        if (/NotAllowedError|PermissionDeniedError|denied|not allowed|permission/i.test(name + msg)) {
                            self.error = 'permission_denied';
                        } else if (/NotFoundError|DevicesNotFoundError|no_camera|not found/i.test(name + msg)) {
                            self.error = 'no_camera';
                        } else if (/NotReadableError|TrackStartError/i.test(name + msg)) {
                            self.error = 'camera_busy';
                        } else {
                            self.error = 'generic:' + msg;
                        }
                    });
            },

            onScanSuccess(decodedText) {
                var self = this;
                self.nomor    = decodedText.trim();
                self.found    = true;
                self.scanning = false;

                if (self.qr) {
                    self.qr.stop().catch(function () {});
                }

                self.navigateToTransaksi(self.nomor);
            },

            submitManual() {
                var self  = this;
                var nomor = self.manualInput.trim();
                if (!nomor) return;

                self.loadingManual = true;
                self.error         = null;
                self.nomor         = nomor;
                self.found         = true;

                self.navigateToTransaksi(nomor);
            },

            navigateToTransaksi(nomor) {
                var self = this;

                fetch(
                    '/admin/api/transaksis/find-by-nomor/' + encodeURIComponent(nomor),
                    {
                        headers: {
                            'Accept'           : 'application/json',
                            'X-Requested-With' : 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                )
                .then(function (res) {
                    if (!res.ok) throw new Error('not_found');
                    return res.json();
                })
                .then(function (data) {
                    if (data.url) {
                        window.location.href = data.url;
                    } else {
                        throw new Error('no_url');
                    }
                })
                .catch(function () {
                    self.found         = false;
                    self.loadingManual = false;
                    self.error         = 'not_found:' + nomor;
                });
            },

            switchCamera(deviceId) {
                var self = this;
                if (!self.qr) return;

                self.qr.stop()
                    .then(function () {
                        self.activeCam = deviceId;
                        return self.qr.start(
                            deviceId,
                            { fps: 10, qrbox: { width: 240, height: 240 } },
                            function (t) { self.onScanSuccess(t); },
                            function () {}
                        );
                    })
                    .catch(function () {});
            },

            retry() {
                if (this.qr) {
                    this.qr.stop().catch(function () {});
                    this.qr = null;
                }
                this.error      = null;
                this.scanning   = false;
                this.found      = false;
                this.nomor      = null;
                this.cameras    = [];

                if (this.isSecure) {
                    this.startCamera();
                }
            },

            destroy() {
                if (this.qr) {
                    this.qr.stop().catch(function () {});
                    this.qr = null;
                }
                this.scanning = false;
            },

            // ── Error messages ───────────────────────────────────────────
            getErrorTitle() {
                if (!this.error) return '';
                var map = {
                    'not_secure'        : '🔒 Koneksi Tidak Aman (HTTP)',
                    'permission_denied' : '🚫 Izin Kamera Ditolak',
                    'no_camera'         : '📷 Kamera Tidak Ditemukan',
                    'camera_busy'       : '⚠️ Kamera Sedang Dipakai Aplikasi Lain',
                    'api_not_available' : '⚠️ Browser Tidak Mendukung Kamera Web',
                    'library_not_ready' : '⚠️ Library QR Belum Siap',
                };
                if (this.error.startsWith('not_found:')) return '❌ Transaksi Tidak Ditemukan';
                return map[this.error] || '⚠️ Gagal Membuka Kamera';
            },

            getErrorBody() {
                if (!this.error) return '';
                if (this.error === 'not_secure') {
                    return 'Browser hanya mengizinkan kamera pada HTTPS atau localhost. ' +
                           'Akses admin via http://localhost:8000/admin lalu coba lagi. ' +
                           'Atau gunakan input manual di bawah.';
                }
                if (this.error === 'permission_denied') {
                    return 'Klik ikon kunci 🔒 / kamera 📷 di address bar → pilih "Izinkan" → ' +
                           'refresh halaman (F5) → buka kembali modal ini. ' +
                           'Atau gunakan input manual di bawah.';
                }
                if (this.error === 'no_camera') {
                    return 'Tidak ada kamera yang terdeteksi. Pastikan webcam laptop aktif dan driver terpasang. ' +
                           'Gunakan input manual di bawah.';
                }
                if (this.error === 'camera_busy') {
                    return 'Kamera dipakai aplikasi lain (video call, aplikasi kamera, dll). ' +
                           'Tutup aplikasi tersebut lalu klik "Coba Lagi".';
                }
                if (this.error === 'api_not_available') {
                    return 'Browser Anda tidak mendukung Web Camera API. Gunakan Chrome atau Edge versi terbaru.';
                }
                if (this.error === 'library_not_ready') {
                    return 'Tutup modal ini dan buka kembali, atau refresh halaman.';
                }
                if (this.error.startsWith('not_found:')) {
                    return 'Transaksi "' + this.error.replace('not_found:', '') + '" tidak ditemukan. Periksa kembali nomor transaksi.';
                }
                return 'Error: ' + this.error;
            },
        };
    });
});
</script>
