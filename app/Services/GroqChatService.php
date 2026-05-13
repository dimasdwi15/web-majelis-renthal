<?php

namespace App\Services;

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
        'cara sewa'         => "**Cara Menyewa di Majelis Adventure:**\n1. Buka katalog barang & pilih alat yang dibutuhkan\n2. Klik tombol Sewa / Booking\n3. Pilih tanggal ambil & tanggal kembali\n4. Upload foto identitas (KTP/Kartu Pelajar)\n5. Pilih metode pembayaran (Midtrans / Tunai)\n6. Konfirmasi pesanan → selesai! 🎒",
        'cara booking'      => "**Cara Menyewa di Majelis Adventure:**\n1. Buka katalog barang & pilih alat yang dibutuhkan\n2. Klik tombol Sewa / Booking\n3. Pilih tanggal ambil & tanggal kembali\n4. Upload foto identitas (KTP/Kartu Pelajar)\n5. Pilih metode pembayaran (Midtrans / Tunai)\n6. Konfirmasi pesanan → selesai! 🎒",
        'harga sewa'        => "**Daftar Harga Sewa (per hari):**\n• Tenda Eiger Guardian — Rp 55.000\n• Carrier Osprey 60L — Rp 45.000\n• Sleeping Bag Eiger — Rp 20.000\n• Sepatu Hiking Salomon — Rp 50.000\n• Jaket Arcteryx — Rp 35.000\n• Kompor Ultralight — Rp 20.000\n• Headlamp — Rp 15.000\n• Dan masih banyak lagi di katalog aplikasi ✨",
        'denda'             => "**Aturan Denda Keterlambatan:**\n• Denda dihitung per hari keterlambatan\n• Besaran denda bergantung pada jenis barang\n• Untuk nominal pasti, admin akan menginformasikan saat pengembalian\n• Barang rusak/hilang dikenakan biaya penggantian sesuai nilai barang",
        'keterlambatan'     => "**Info Keterlambatan:**\n• Harap kembalikan barang tepat waktu sesuai tanggal yang disepakati\n• Jika ada kendala, segera hubungi admin sebelum melewati batas waktu\n• Denda berlaku mulai hari pertama keterlambatan\n• Perpanjangan sewa bisa dilakukan via WhatsApp admin sebelum jatuh tempo",
        'pembayaran'        => "**Metode Pembayaran:**\n• **Midtrans** — Bayar online via transfer, QRIS, e-wallet, kartu kredit\n• **Tunai (COD)** — Bayar langsung saat ambil barang\n\nBatas waktu pembayaran online: 24 jam setelah checkout",
        'syarat'            => "**Syarat & Ketentuan Sewa:**\n• Wajib upload identitas (KTP / Kartu Pelajar)\n• Usia minimal 17 tahun atau didampingi orang tua\n• Barang dikembalikan dalam kondisi bersih dan baik\n• Kerusakan/kehilangan menjadi tanggung jawab penyewa\n• Dilarang menyewakan ulang barang kepada pihak lain",
        'stok'              => "Untuk mengecek ketersediaan stok terkini, silakan lihat langsung di halaman katalog aplikasi. Stok diperbarui secara real-time. Atau hubungi admin WhatsApp untuk konfirmasi cepat! 📦",
        'kontak'            => "**Hubungi Admin Majelis Adventure:**\n📱 WhatsApp: 0813-5860-9650\n⏰ Jam layanan: Senin–Minggu, 08.00–21.00 WIB\n\nAdmin siap membantu booking, konfirmasi pembayaran, dan pertanyaan lainnya!",
        'jam operasional'   => "⏰ **Jam Operasional:**\nSenin – Minggu: 08.00 – 21.00 WIB\n\nDi luar jam tersebut, Anda masih bisa melakukan order di aplikasi. Admin akan memproses pesanan saat jam buka.",
    ];

    public function __construct()
    {
        $this->apiKey = config('services.ai.groq_api_key', '');
        $this->model  = config('services.ai.groq_model', 'llama-3.3-70b-versatile');
    }

    // ────────────────────────────────────────────────────────────────────────
    // PUBLIC: titik masuk utama
    // ────────────────────────────────────────────────────────────────────────

    /**
     * @param  string  $userMessage
     * @param  array   $history      [['role'=>'user','content'=>'...'], ...]
     * @return array{reply: string, need_admin: bool, whatsapp_url: string|null, tokens_used: int, from_faq: bool}
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

        // 2. Cek cache (response identik dari AI)
        $cacheKey = 'chat_ai_' . md5(strtolower(trim($userMessage)));
        if ($cached = Cache::get($cacheKey)) {
            return array_merge($cached, ['from_faq' => false]);
        }

        // 3. Kirim ke Groq
        $result = $this->callGroq($userMessage, $history);

        // 4. Cache selama 30 menit untuk pesan umum (bukan spesifik user)
        if (!$result['need_admin']) {
            Cache::put($cacheKey, $result, 1800);
        }

        return array_merge($result, ['from_faq' => false]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
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
                    'max_tokens'  => 600,
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

            $data        = $response->json();
            $replyText   = $data['choices'][0]['message']['content'] ?? '';
            $tokensUsed  = $data['usage']['total_tokens'] ?? 0;

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
        // AI akan menyisipkan [NEED_ADMIN] jika perlu eskalasi
        $needAdmin = str_contains($text, '[NEED_ADMIN]');
        $clean     = trim(str_replace('[NEED_ADMIN]', '', $text));
        return [$clean, $needAdmin];
    }

    private function buildWhatsAppUrl(string $userMessage): string
    {
        $pesan = "Halo Admin Majelis Adventure 👋\n\nSaya perlu bantuan terkait:\n\"$userMessage\"\n\nMohon dibantu, terima kasih!";
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
        // Ambil max 10 pesan terakhir agar tidak melebihi context window
        $recent = array_slice($history, -10);
        return array_map(fn($h) => [
            'role'    => $h['role'],
            'content' => $h['content'],
        ], $recent);
    }

    private function buildSystemPrompt(): string
    {
        $now = now()->setTimezone('Asia/Jakarta')->format('d F Y, H:i') . ' WIB';

        return <<<PROMPT
Kamu adalah **Asisten Virtual Majelis Adventure** — aplikasi penyewaan alat outdoor terpercaya di Jember, Jawa Timur.

**Tanggal & Waktu Sekarang:** $now

---

## PRODUK YANG TERSEDIA (per hari):
**Apparel:** Tenda Eiger Guardian (Rp55rb/hr, kap. 2 org), Celana Hiking Decathlon (Rp15rb), Sepatu Hiking Salomon (Rp50rb), Jaket Bulang TNF (Rp30rb), Jaket Arcteryx (Rp35rb), Jas Hujan Trekking (Rp20rb), Tas Hydropack (Rp25rb)
**Camping Gear:** Sleeping Bag Eiger (Rp20rb), Headlamp (Rp15rb), Meja Lipat (Rp20rb), Kursi Lipat (Rp15rb), Flysheet (Rp35rb), Matras Eiger (Rp10rb), Tarp Tent (Rp50rb)
**Hiking Gear:** Trekking Pole (Rp15rb), Carrier Osprey 60L (Rp45rb)
**Cooking Equipment:** Cooking Set (Rp10rb), Kompor Ultralight (Rp20rb)
**Accessories:** Botol Minum Arei 1L (Rp5rb)

## CARA SEWA:
1. Pilih barang di katalog → klik Sewa
2. Tentukan tanggal ambil & kembali
3. Upload identitas (KTP / Kartu Pelajar)
4. Bayar via Midtrans (online) atau Tunai saat ambil
5. Ambil barang di lokasi Majelis Adventure, Jember

## ATURAN PENTING:
- Denda keterlambatan: berlaku per hari sesuai kebijakan barang
- Barang rusak/hilang: wajib lapor ke admin, biaya penggantian sesuai nilai barang
- Perpanjangan sewa: hubungi admin WhatsApp **sebelum** jatuh tempo
- Batas pembayaran online: 24 jam setelah checkout

## KONTAK ADMIN:
WhatsApp: 0813-5860-9650 | Jam: Senin–Minggu 08.00–21.00 WIB

---

## INSTRUKSI PERILAKU:
- Jawab dalam Bahasa Indonesia yang **ramah, sopan, dan profesional**
- Gunakan emoji secukupnya agar terasa hangat (🏕️🎒⛺🌄)
- Format jawaban dengan markdown sederhana (bold, list)
- Jawaban **maksimal 3 paragraf** atau daftar singkat — jangan terlalu panjang
- Jika pertanyaan di **luar konteks rental outdoor** (politik, berita, dll): tolak dengan sopan dan fokuskan kembali
- Jika kamu **tidak yakin** atau pertanyaan membutuhkan data spesifik user (nomor transaksi, status pembayaran real-time, dll): tambahkan teks `[NEED_ADMIN]` di awal jawaban dan sarankan hubungi admin
- **JANGAN** mengarang data stok, harga, atau status transaksi yang tidak ada di daftar ini

## KAPAN MENAMBAHKAN [NEED_ADMIN]:
- Pertanyaan tentang status transaksi spesifik
- Komplain kerusakan atau kehilangan
- Perpanjangan sewa yang sudah berjalan
- Masalah pembayaran atau refund
- Pertanyaan yang tidak bisa dijawab dengan informasi di atas

PROMPT;
    }
}
