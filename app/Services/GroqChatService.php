<?php

namespace App\Services;

use App\Models\Barang;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GroqChatService
{
    private string $apiKey;
    private string $model; 
    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private string $waNumber = '6281358609650';

    // ── FAQ lokal — hemat token Groq ─────────────────────────────────────────
    private array $faqMap = [
        'cara sewa'         => "**Cara Menyewa di Majelis Adventure:**\n1. Buka **Katalog** → pilih barang\n2. Tap **Tambah ke Keranjang**, atur jumlah\n3. Buka **Keranjang** → tap **Checkout**\n4. Pilih tanggal ambil & kembali\n5. Upload foto identitas (KTP/SIM/Kartu Pelajar)\n6. Pilih metode bayar: **Midtrans** (online) atau **Tunai** (COD)\n7. Centang S&K → **Konfirmasi Pesanan** ✅",

        'cara booking'      => "**Cara Menyewa di Majelis Adventure:**\n1. Buka **Katalog** → pilih barang\n2. Tap **Tambah ke Keranjang**, atur jumlah\n3. Buka **Keranjang** → tap **Checkout**\n4. Pilih tanggal ambil & kembali\n5. Upload foto identitas (KTP/SIM/Kartu Pelajar)\n6. Pilih metode bayar: **Midtrans** (online) atau **Tunai** (COD)\n7. Centang S&K → **Konfirmasi Pesanan** ✅",

        'denda'             => "**Aturan Denda:**\n• **Keterlambatan**: 50% dari total biaya sewa\n• **Kerusakan**: ditentukan admin berdasarkan kondisi barang\n• **Hilang**: biaya penggantian sesuai nilai barang\n\nSemua denda diselesaikan saat pengembalian. Hubungi admin jika ada kendala 🙏",

        'keterlambatan'     => "**Info Keterlambatan:**\n• Denda 50% dari total biaya sewa jika melewati tanggal kembali\n• Perpanjangan bisa dilakukan via WhatsApp admin **sebelum** jatuh tempo\n• Status transaksi akan berubah ke **Terlambat** secara otomatis\n\nSegeralah hubungi admin jika ada kendala agar bisa diselesaikan dengan baik 🤝",

        'pembayaran'        => "**Metode Pembayaran:**\n• **Midtrans (Cashless)** — transfer bank, QRIS, e-wallet, kartu kredit/debit. Batas bayar 24 jam.\n• **Tunai (COD)** — bayar langsung saat ambil barang di lokasi. Batas konfirmasi 24 jam.\n\n⚠️ Jika tidak dibayar/dikonfirmasi dalam 24 jam, pesanan otomatis **dibatalkan**.",

        'syarat'            => "**Syarat & Ketentuan Sewa:**\n• Wajib upload identitas (KTP / SIM / Kartu Pelajar)\n• Usia minimal 17 tahun atau didampingi orang tua\n• Barang dikembalikan dalam kondisi bersih dan baik\n• Kerusakan/kehilangan menjadi tanggung jawab penyewa\n• Dilarang menyewakan ulang barang kepada pihak lain\n• Perpanjangan sewa harus konfirmasi ke admin sebelum jatuh tempo",

        'stok'              => "Untuk stok real-time, silakan cek langsung di halaman **Katalog** aplikasi — stok diperbarui otomatis. Atau tanyakan ke saya barang spesifik yang Anda butuhkan! 📦",

        'kontak'            => "**Hubungi Admin Majelis Adventure:**\n📱 WhatsApp: 0813-5860-9650\n⏰ Jam layanan: Senin–Minggu, 08.00–21.00 WIB\n\nAdmin siap membantu booking, konfirmasi pembayaran, dan pertanyaan lainnya!",

        'jam operasional'   => "⏰ **Jam Operasional:**\nSenin – Minggu: 08.00 – 21.00 WIB\n\nDi luar jam tersebut, Anda masih bisa order di aplikasi. Admin akan memproses saat jam buka.",

        'status transaksi'  => "**Status Transaksi di Aplikasi:**\n• ⏳ **Menunggu Pembayaran** — pesanan dibuat, belum dibayar\n• ✅ **Dibayar** — pembayaran berhasil, menunggu admin\n• 🚀 **Berjalan** — barang sedang disewa\n• ⚠️ **Terlambat** — melewati tanggal kembali\n• 📦 **Dikembalikan** — barang sudah dikembalikan, admin memeriksa\n• 🎉 **Selesai** — transaksi selesai\n• ❌ **Dibatalkan** — pesanan dibatalkan\n\nCek status di menu **Riwayat** pada aplikasi.",

        'riwayat'           => "Semua transaksi Anda bisa dilihat di menu **Riwayat** (ikon jam di bawah layar). Di sana ada daftar transaksi aktif dan selesai, lengkap dengan status dan detail item yang disewa. 📜",

        'notifikasi'        => "Notifikasi dikirim otomatis untuk:\n• Konfirmasi pesanan berhasil\n• Status pembayaran sukses\n• Barang siap diambil\n• Reminder jatuh tempo pengembalian\n• Update dari admin\n\nPastikan notifikasi aplikasi aktif di pengaturan HP Anda 🔔",

        'rekomendasi'       => "**Fitur Rekomendasi AI di Majelis Adventure:**\n• 📸 **Rekomendasi Foto** — Upload foto lokasi/situasi → AI sarankan alat yang cocok\n• 🌤️ **Rekomendasi Cuaca** — Berdasarkan cuaca terkini, AI rekomendasikan alat yang dibutuhkan (misal hujan → jas hujan, flysheet)\n\nFitur ini ada di halaman **Rekomendasi** di aplikasi!",

        'cuaca'             => "**Fitur Rekomendasi Cuaca:**\nAplikasi bisa membaca cuaca terkini di lokasi Anda dan merekomendasikan alat outdoor yang paling tepat! 🌤️⛈️\n\nMisal: cuaca mendung/hujan → AI akan rekomendasikan jas hujan, flysheet, tarp tent.\nAkses fitur ini di menu **Rekomendasi** di aplikasi.",

        'keranjang'         => "**Cara Menggunakan Keranjang:**\n1. Tap barang di katalog → tap **Tambah ke Keranjang**\n2. Atur jumlah (qty) sesuai kebutuhan\n3. Buka ikon keranjang → review semua item\n4. Tap **Checkout** untuk lanjut memesan\n\nAnda bisa menambahkan banyak barang sekaligus dalam satu transaksi! 🛒",

        'perpanjang'        => "**Perpanjangan Sewa:**\nHubungi admin via WhatsApp **sebelum** tanggal kembali:\n📱 0813-5860-9650\n\n⚠️ Jika sudah melewati tanggal kembali, denda keterlambatan (50% total sewa) tetap berlaku.",

        'lokasi'            => "📍 **Lokasi Majelis Adventure:**\nJember, Jawa Timur\n\nAmbil & kembalikan barang langsung di basecamp kami. Untuk alamat detail, hubungi admin WhatsApp: 0813-5860-9650",
    ];

    public function __construct()
    {
        $this->apiKey = config('services.ai.groq_api_key', '');
        $this->model  = config('services.ai.groq_model', 'llama-3.3-70b-versatile');
    }

    // ────────────────────────────────────────────────────────────────────────
    // PUBLIC
    // ────────────────────────────────────────────────────────────────────────

    /**
     * @return array{reply:string, need_admin:bool, whatsapp_url:string|null, tokens_used:int, from_faq:bool}
     */
    public function chat(string $userMessage, array $history = []): array
    {
        // 1. Cek FAQ lokal dulu (hemat token)
        $faqAnswer = $this->matchFaq($userMessage);
        if ($faqAnswer) {
            return [
                'reply'        => $faqAnswer,
                'need_admin'   => false,
                'whatsapp_url' => null,
                'tokens_used'  => 0,
                'from_faq'     => true,
            ];
        }

        // 2. Cek cache respons AI (untuk pertanyaan umum yang identik)
        $cacheKey = 'chat_ai_' . md5(strtolower(trim($userMessage)));
        if ($cached = Cache::get($cacheKey)) {
            return array_merge($cached, ['from_faq' => false]);
        }

        // 3. Kirim ke Groq
        $result = $this->callGroq($userMessage, $history);

        // 4. Cache 5 menit untuk pesan umum
        if (!$result['need_admin']) {
            Cache::put($cacheKey, $result, 300);
        }

        return array_merge($result, ['from_faq' => false]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PRIVATE
    // ────────────────────────────────────────────────────────────────────────

    private function matchFaq(string $message): ?string
    {
        $msg = mb_strtolower(trim($message));
        foreach ($this->faqMap as $keyword => $answer) {
            if (str_contains($msg, $keyword)) {
                return $answer;
            }
        }
        return null;
    }

    private function callGroq(string $userMessage, array $history): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt()],
            ...$this->formatHistory($history),
            ['role' => 'user', 'content' => $userMessage],
        ];

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->retry(2, 1000)
                ->post($this->apiUrl, [
                    'model'       => $this->model,
                    'messages'    => $messages,
                    'max_tokens'  => 700,
                    'temperature' => 0.4,
                    'top_p'       => 0.9,
                ]);

            if ($response->failed()) {
                Log::error('Groq API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->fallbackResponse($userMessage);
            }

            $data       = $response->json();
            $replyText  = $data['choices'][0]['message']['content'] ?? '';
            $tokensUsed = $data['usage']['total_tokens'] ?? 0;

            [$reply, $needAdmin] = $this->parseReply($replyText);
            $waUrl = $needAdmin ? $this->buildWhatsAppUrl($userMessage) : null;

            return [
                'reply'        => $reply,
                'need_admin'   => $needAdmin,
                'whatsapp_url' => $waUrl,
                'tokens_used'  => $tokensUsed,
            ];
        } catch (\Throwable $e) {
            Log::error('Groq chat exception', ['msg' => $e->getMessage()]);
            return $this->fallbackResponse($userMessage);
        }
    }

    private function parseReply(string $text): array
    {
        $needAdmin = str_contains($text, '[NEED_ADMIN]');
        $clean     = trim(str_replace('[NEED_ADMIN]', '', $text));
        return [$clean, $needAdmin];
    }

    private function buildWhatsAppUrl(string $userMessage): string
    {
        $pesan = "Halo Admin Majelis Adventure 👋\n\nSaya perlu bantuan terkait:\n\"{$userMessage}\"\n\nMohon dibantu, terima kasih!";
        return "https://wa.me/{$this->waNumber}?text=" . urlencode($pesan);
    }

    private function fallbackResponse(string $userMessage): array
    {
        return [
            'reply'        => "Maaf, saya sedang mengalami gangguan teknis 🙏 Silakan hubungi admin kami melalui WhatsApp untuk mendapat bantuan segera.",
            'need_admin'   => true,
            'whatsapp_url' => $this->buildWhatsAppUrl($userMessage),
            'tokens_used'  => 0,
        ];
    }

    private function formatHistory(array $history): array
    {
        $recent = array_slice($history, -10);
        return array_map(fn($h) => [
            'role'    => $h['role'],
            'content' => $h['content'],
        ], $recent);
    }

    // ────────────────────────────────────────────────────────────────────────
    // DYNAMIC CATALOG (real-time dari database, cache 5 menit)
    // Cache otomatis di-reset oleh BarangObserver saat data berubah.
    // ────────────────────────────────────────────────────────────────────────

    private function buildCatalogContext(): string
    {
        return Cache::remember('chat_catalog_context', 300, function () {
            $barang = Barang::tersedia()
                ->with(['kategori', 'tags'])
                ->orderBy('kategori_barang_id')
                ->orderBy('nama')
                ->get();

            if ($barang->isEmpty()) {
                return "*(Tidak ada barang yang tersedia saat ini. Hubungi admin untuk info lebih lanjut.)*";
            }

            $grouped = $barang->groupBy(fn($b) => $b->kategori?->nama ?? 'Lainnya');

            $lines = [];
            foreach ($grouped as $kategori => $items) {
                $lines[] = "\n**{$kategori}:**";
                foreach ($items as $item) {
                    $harga  = 'Rp ' . number_format($item->harga_per_hari, 0, ',', '.');
                    $stok   = $item->stok;
                    $labels = $item->tags->pluck('label')->filter()->implode(', ');
                    $tagStr = $labels ? " _(tag: {$labels})_" : '';
                    $lines[] = "• {$item->nama} — {$harga}/hari | stok: {$stok}{$tagStr}";
                }
            }

            $updatedAt = now()->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB';
            $lines[]   = "\n_Data diperbarui: {$updatedAt}_";

            return implode("\n", $lines);
        });
    }

    // ────────────────────────────────────────────────────────────────────────
    // SYSTEM PROMPT — komprehensif & dinamis
    // ────────────────────────────────────────────────────────────────────────

    private function buildSystemPrompt(): string
    {
        $now            = now()->setTimezone('Asia/Jakarta')->format('d F Y, H:i') . ' WIB';
        $catalogContext = $this->buildCatalogContext();

        return <<<PROMPT
Kamu adalah **Asisten Virtual Majelis Adventure** 🏕️ — aplikasi penyewaan alat outdoor terpercaya di Jember, Jawa Timur.

**Waktu Sekarang:** {$now}

---

## TENTANG PLATFORM

Majelis Adventure terdiri dari dua sistem yang terintegrasi:
- **Aplikasi Mobile (Flutter)** — digunakan pelanggan untuk menyewa, melihat katalog, membayar, dan memantau status.
- **Dashboard Admin (Laravel/Filament)** — digunakan admin untuk mengelola barang, transaksi, dan laporan.

---

## FITUR LENGKAP APLIKASI MOBILE

### 🏠 Beranda (Home)
- Banner promo, kategori barang, dan produk unggulan
- Fitur pencarian barang
- Info cuaca terkini dan rekomendasi berdasarkan cuaca

### 🛍️ Katalog
- Daftar semua barang yang tersedia untuk disewa
- Filter berdasarkan kategori (Tenda, Carrier, Sleeping Bag, dll)
- Search/cari nama barang
- Ketuk barang → buka halaman Detail Barang

### 📦 Detail Barang
- Foto-foto barang, deskripsi lengkap, spesifikasi, tag/label
- Harga per hari dan info stok
- Tombol "Tambah ke Keranjang"
- Tombol ikon chat → langsung tanya AI tentang barang tersebut

### 🛒 Keranjang (Cart)
- Tampung barang yang akan disewa dari berbagai kategori
- Atur jumlah (qty) per barang
- Total harga per hari dihitung otomatis
- Tombol "Checkout" untuk lanjut ke pemesanan

### 📋 Checkout
- Pilih tanggal ambil & tanggal kembali (maks. 90 hari ke depan)
- Upload foto jaminan identitas: **KTP**, **SIM**, atau **Kartu Pelajar**
- Pilih metode pembayaran: **Midtrans** (cashless/online) atau **Tunai/COD** (bayar saat ambil)
- Review detail biaya (harga/hari × durasi hari)
- Centang syarat & ketentuan → Konfirmasi Pesanan

### 📜 Riwayat Transaksi (History)
- Daftar semua transaksi user (aktif & selesai)
- Detail transaksi: status, barang disewa, tanggal ambil/kembali, total bayar
- Filter: Aktif / Selesai / Dibatalkan

### 👤 Profil
- Lihat & edit data diri (nama, foto profil, nomor telepon)
- Ganti password
- Login via email/password atau Google Sign-In
- Tombol logout

### 🔔 Notifikasi
- Update status transaksi (pembayaran sukses, barang siap diambil, dll)
- Reminder jatuh tempo pengembalian
- Info promo dari admin

### 🤖 Chat AI (Asisten Majelis — halaman ini)
- Tanya jawab seputar penyewaan alat outdoor
- Riwayat chat tersimpan per sesi
- Bisa eskalasi ke WhatsApp admin jika perlu bantuan lebih lanjut

### 📸 Rekomendasi AI
- **Rekomendasi Foto**: Upload foto situasi/lokasi → AI menyarankan alat outdoor yang cocok
- **Rekomendasi Cuaca**: Berdasarkan data cuaca real-time di lokasi user, AI merekomendasikan alat yang dibutuhkan (contoh: cuaca hujan → jas hujan, flysheet, tarp tent)

---

## ALUR SEWA LENGKAP

1. Buka **Katalog** → pilih barang yang dibutuhkan
2. Tap **"Tambah ke Keranjang"** → atur jumlah unit jika perlu
3. Buka **Keranjang** → tap **"Checkout"**
4. Di halaman Checkout:
   - Pilih **tanggal ambil** dan **tanggal kembali**
   - Upload **foto identitas** (KTP / SIM / Kartu Pelajar)
   - Pilih **metode pembayaran**
   - Centang syarat & ketentuan
   - Tap **"Konfirmasi Pesanan"**
5. **Jika Midtrans**: selesaikan pembayaran dalam 24 jam → status jadi **Dibayar**
6. Admin memproses → status **Berjalan** → barang bisa diambil di lokasi
7. Kembalikan barang tepat waktu → status **Dikembalikan** → **Selesai**

---

## STATUS TRANSAKSI

| Status | Keterangan |
|--------|-----------|
| ⏳ **Menunggu Pembayaran** | Pesanan dibuat, belum dibayar. Batas 24 jam. |
| ✅ **Dibayar** | Pembayaran berhasil, menunggu konfirmasi admin. |
| 🚀 **Berjalan** | Barang sedang disewa (sudah diambil). |
| ⚠️ **Terlambat** | Melewati tanggal kembali, belum dikembalikan. |
| 📦 **Dikembalikan** | Barang sudah dikembalikan, admin sedang memeriksa kondisi. |
| 🎉 **Selesai** | Transaksi selesai sempurna. |
| ❌ **Dibatalkan** | Pesanan dibatalkan (otomatis jika COD tidak dikonfirmasi 24 jam, atau dibatalkan manual). |

---

## METODE PEMBAYARAN

- **Midtrans (Cashless)**: Bayar online via transfer bank, QRIS, e-wallet, kartu kredit/debit. Batas bayar **24 jam** setelah checkout.
- **Tunai (COD)**: Bayar langsung saat mengambil barang di lokasi. Harus dikonfirmasi dalam **24 jam** atau transaksi dibatalkan otomatis.

---

## ATURAN & KEBIJAKAN

- **Denda keterlambatan**: **50% dari total biaya sewa** jika melewati tanggal kembali
- **Denda kerusakan**: Ditentukan admin berdasarkan kondisi barang, dibayar saat pengembalian
- **Barang hilang**: Wajib lapor ke admin, biaya penggantian sesuai nilai barang
- **Perpanjangan sewa**: Hubungi admin WhatsApp **sebelum** jatuh tempo
- **Pembatalan otomatis**: COD batal jika tidak dikonfirmasi dalam 24 jam
- **Usia minimal**: 17 tahun atau didampingi orang tua
- **Dilarang**: Menyewakan ulang barang kepada pihak lain

---

## KATALOG PRODUK (real-time dari database, diperbarui otomatis):

{$catalogContext}

---

## KONTAK ADMIN

📱 WhatsApp: 0813-5860-9650
⏰ Jam Layanan: Senin–Minggu, 08.00–21.00 WIB
📍 Lokasi: Jember, Jawa Timur

---

## INSTRUKSI PERILAKU

- Jawab dalam Bahasa Indonesia yang **ramah, sopan, dan profesional**
- Gunakan emoji secukupnya agar terasa hangat (🏕️🎒⛺🌄)
- Format dengan markdown sederhana (bold, list)
- Jawaban **maksimal 3 paragraf** atau daftar singkat — jangan terlalu panjang
- Jika pertanyaan di **luar konteks rental outdoor**: tolak dengan sopan dan arahkan kembali
- Jika **tidak yakin** atau butuh data spesifik user (nomor transaksi, status real-time, dll): tambahkan `[NEED_ADMIN]` di awal jawaban
- **JANGAN** mengarang data yang tidak ada dalam informasi di atas

## KAPAN MENAMBAHKAN [NEED_ADMIN]

- Pertanyaan tentang status transaksi spesifik user (perlu nomor transaksi)
- Komplain kerusakan atau kehilangan barang
- Permintaan perpanjangan sewa yang sudah berjalan
- Masalah pembayaran, refund, atau pembatalan
- Pertanyaan yang benar-benar tidak dapat dijawab dari informasi di atas

PROMPT;
    }
}
