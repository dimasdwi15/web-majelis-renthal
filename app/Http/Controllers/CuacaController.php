<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CuacaController extends Controller
{
    /**
     * Lokasi camping populer di Indonesia beserta koordinatnya.
     * Tidak disimpan ke database — hanya untuk kebutuhan fitur cuaca.
     */
    public static function daftarLokasi(): array
    {
        return [
            ['id' => 'rinjani',      'nama' => 'Gunung Rinjani',       'provinsi' => 'NTB',          'lat' => -8.4119,  'lon' => 116.4650],
            ['id' => 'semeru',       'nama' => 'Gunung Semeru',        'provinsi' => 'Jawa Timur',   'lat' => -8.1076,  'lon' => 112.9223],
            ['id' => 'bromo',        'nama' => 'Gunung Bromo',         'provinsi' => 'Jawa Timur',   'lat' => -7.9425,  'lon' => 112.9531],
            ['id' => 'merapi',       'nama' => 'Gunung Merapi',        'provinsi' => 'Yogyakarta',   'lat' => -7.5407,  'lon' => 110.4457],
            ['id' => 'prau',         'nama' => 'Gunung Prau',          'provinsi' => 'Jawa Tengah',  'lat' => -7.1878,  'lon' => 109.9222],
            ['id' => 'sindoro',      'nama' => 'Gunung Sindoro',       'provinsi' => 'Jawa Tengah',  'lat' => -7.3026,  'lon' => 109.9987],
            ['id' => 'slamet',       'nama' => 'Gunung Slamet',        'provinsi' => 'Jawa Tengah',  'lat' => -7.2429,  'lon' => 109.2079],
            ['id' => 'lawu',         'nama' => 'Gunung Lawu',          'provinsi' => 'Jawa Tengah',  'lat' => -7.6326,  'lon' => 111.1921],
            ['id' => 'gede',         'nama' => 'Gunung Gede',          'provinsi' => 'Jawa Barat',   'lat' => -6.7814,  'lon' => 106.9792],
            ['id' => 'pangrango',    'nama' => 'Gunung Pangrango',     'provinsi' => 'Jawa Barat',   'lat' => -6.7900,  'lon' => 106.9885],
            ['id' => 'cikuray',      'nama' => 'Gunung Cikuray',       'provinsi' => 'Jawa Barat',   'lat' => -7.3213,  'lon' => 107.8925],
            ['id' => 'papandayan',   'nama' => 'Gunung Papandayan',    'provinsi' => 'Jawa Barat',   'lat' => -7.3213,  'lon' => 107.7306],
            ['id' => 'argopuro',     'nama' => 'Gunung Argopuro',      'provinsi' => 'Jawa Timur',   'lat' => -7.9748,  'lon' => 113.5710],
            ['id' => 'arjuno',       'nama' => 'Gunung Arjuno',        'provinsi' => 'Jawa Timur',   'lat' => -7.7285,  'lon' => 112.5940],
            ['id' => 'merbabu',      'nama' => 'Gunung Merbabu',       'provinsi' => 'Jawa Tengah',  'lat' => -7.4556,  'lon' => 110.4350],
            ['id' => 'andong',       'nama' => 'Gunung Andong',        'provinsi' => 'Jawa Tengah',  'lat' => -7.4127,  'lon' => 110.5428],
            ['id' => 'kelimutu',     'nama' => 'Gunung Kelimutu',      'provinsi' => 'NTT',          'lat' => -8.7715,  'lon' => 121.8121],
            ['id' => 'tambora',      'nama' => 'Gunung Tambora',       'provinsi' => 'NTB',          'lat' => -8.2473,  'lon' => 117.9965],
            ['id' => 'agung',        'nama' => 'Gunung Agung',         'provinsi' => 'Bali',         'lat' => -8.3428,  'lon' => 115.5079],
            ['id' => 'batur',        'nama' => 'Gunung Batur',         'provinsi' => 'Bali',         'lat' => -8.2421,  'lon' => 115.3751],
            ['id' => 'kerinci',      'nama' => 'Gunung Kerinci',       'provinsi' => 'Sumatera Barat', 'lat' => -1.6969, 'lon' => 101.2638],
            ['id' => 'sinabung',     'nama' => 'Gunung Sinabung',      'provinsi' => 'Sumatera Utara', 'lat' => 3.1701,  'lon' => 98.3920],
            ['id' => 'lokon',        'nama' => 'Gunung Lokon',         'provinsi' => 'Sulawesi Utara', 'lat' => 1.3582,  'lon' => 124.7927],
            ['id' => 'latimojong',   'nama' => 'Gunung Latimojong',    'provinsi' => 'Sulawesi Selatan', 'lat' => -3.3800, 'lon' => 120.0167],
        ];
    }

    /**
     * Mapping kondisi cuaca → kategori barang yang direkomendasikan.
     * Key = weather condition group (dari OpenWeatherMap), value = array kategori nama.
     */
    private function rekomendasi(): array
    {
        return [
            // 🌧️ Hujan / badai
            'rain' => ['camping gear', 'apparel', 'hiking gear'],
            'drizzle' => ['camping gear', 'apparel'],
            'thunderstorm' => ['camping gear', 'apparel', 'hiking gear'],

            // ☀️ Cerah
            'clear' => ['hiking gear', 'cooking equipment', 'apparel'],

            // ☁️ Berawan
            'clouds' => ['camping gear', 'hiking gear'],

            // 🌫️ Kabut
            'mist' => ['camping gear', 'apparel'],
            'fog' => ['camping gear'],
            'haze' => ['camping gear'],
            'smoke' => ['camping gear'],

            // ❄️ Snow
            'snow' => ['camping gear', 'apparel', 'hiking gear'],

            // 🔁 Default
            'default' => ['camping gear', 'hiking gear'],
        ];
    }

    /**
     * Endpoint AJAX: ambil cuaca & rekomendasi barang.
     * GET /api/cuaca?lokasi_id={id}&tanggal_ambil={Y-m-d}
     */
    public function cek(Request $request)
    {
        $request->validate([
            'lokasi_id'     => ['required', 'string'],
            'tanggal_ambil' => ['required', 'date'],
        ]);

        // Temukan lokasi dari daftar statis
        $lokasiList = self::daftarLokasi();
        $lokasi = collect($lokasiList)->firstWhere('id', $request->lokasi_id);

        if (!$lokasi) {
            return response()->json(['message' => 'Lokasi tidak ditemukan.'], 404);
        }

        if ($request->tanggal_ambil > now()->addDays(5)) {
            return response()->json([
                'lokasi' => $lokasi['nama'],
                'provinsi' => $lokasi['provinsi'],
                'cuaca' => null,
                'rekomendasi' => [],
                'message' => 'Prediksi cuaca hanya tersedia hingga 5 hari ke depan.'
            ], 200);
        }

        $apiKey = config('services.openweather.key');

        if (!$apiKey) {
            return response()->json(['message' => 'API key cuaca belum dikonfigurasi.'], 500);
        }

        try {
            // Gunakan forecast 5 hari / 3 jam dari OpenWeatherMap (gratis)
            $response = Http::timeout(8)->get('https://api.openweathermap.org/data/2.5/forecast', [
                'lat'   => $lokasi['lat'],
                'lon'   => $lokasi['lon'],
                'appid' => $apiKey,
                'units' => 'metric',
                'lang'  => 'id',
                'cnt'   => 8, // 8 slot × 3 jam = 24 jam ke depan
            ]);

            if ($response->failed()) {
                return response()->json([
                    'lokasi' => $lokasi['nama'],
                    'provinsi' => $lokasi['provinsi'],
                    'cuaca' => null,
                    'rekomendasi' => [],
                    'message' => 'Cuaca tidak tersedia saat ini.'
                ], 200);
            }

            $data = $response->json();

            // Ambil forecast paling relevan dengan tanggal_ambil
            $tanggalAmbil = $request->tanggal_ambil;
            $forecastList = $data['list'] ?? [];

            // Cari forecast yang tanggalnya paling dekat dengan tanggal ambil
            $forecast = null;
            foreach ($forecastList as $item) {
                $tgl = date('Y-m-d', $item['dt']);
                if ($tgl === $tanggalAmbil) {
                    $forecast = $item;
                    break;
                }
            }

            // Jika tidak ada yang tepat (misal tanggal terlalu jauh), pakai yang pertama
            if (!$forecast) {
                $forecast = $forecastList[0] ?? null;
            }

            if (!$forecast) {
                return response()->json(['message' => 'Data forecast tidak tersedia.'], 422);
            }

            // Parse data cuaca
            $cuacaMain = strtolower($forecast['weather'][0]['main'] ?? 'default');
            $cuacaDesc = $forecast['weather'][0]['description'] ?? '-';
            $suhu      = round($forecast['main']['temp'] ?? 0);
            $suhuMin   = round($forecast['main']['temp_min'] ?? 0);
            $suhuMax   = round($forecast['main']['temp_max'] ?? 0);
            $kelembaban = $forecast['main']['humidity'] ?? 0;
            $angin     = round(($forecast['wind']['speed'] ?? 0) * 3.6, 1); // m/s → km/h
            $iconCode  = $forecast['weather'][0]['icon'] ?? '01d';

            // Tentukan kategori rekomendasi
            $rekMap = $this->rekomendasi();
            $kategoriRek = $rekMap[$cuacaMain] ?? $rekMap['default'];

            // Ambil barang aktif dari kategori yang direkomendasikan
            $barangRek = Barang::with(['fotoUtama', 'kategori'])
                ->whereHas('kategori', fn($q) => $q->whereIn('nama', $kategoriRek))
                ->where('status', 'aktif')
                ->where('stok', '>', 0)
                ->orderByRaw("FIELD(kategori_barang_id, " . $this->urgensiKategori($cuacaMain) . ")")
                ->limit(6)
                ->get()
                ->map(fn($b) => [
                    'id'         => $b->id,
                    'nama'       => $b->nama,
                    'kategori'   => $b->kategori->nama ?? '-',
                    'harga'      => (float) $b->harga_per_hari,
                    'stok'       => $b->stok,
                    'foto'       => $b->fotoUtama?->path_foto,
                ]);

            // Tentukan level peringatan cuaca
            $level = $this->levelCuaca($cuacaMain, $suhu);

            return response()->json([
                'lokasi'     => $lokasi['nama'],
                'provinsi'   => $lokasi['provinsi'],
                'cuaca' => [
                    'main'       => $cuacaMain,
                    'deskripsi'  => ucfirst($cuacaDesc),
                    'suhu'       => $suhu,
                    'suhu_min'   => $suhuMin,
                    'suhu_max'   => $suhuMax,
                    'kelembaban' => $kelembaban,
                    'angin'      => $angin,
                    'icon'       => "https://openweathermap.org/img/wn/{$iconCode}@2x.png",
                    'level'      => $level['level'],
                    'pesan'      => $level['pesan'],
                    'warna'      => $level['warna'],
                ],
                'rekomendasi' => $barangRek,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tentukan urgensi kategori berdasarkan cuaca untuk ordering SQL.
     */
    private function urgensiKategori(string $cuaca): string
    {
        // Ambil ID kategori dari DB — ini mapping sederhana berdasarkan data seed
        // Tenda=1, Carrier=2, SleepingBag=3, Sepatu=4, Kompor=5
        return match (true) {
            in_array($cuaca, ['rain', 'drizzle', 'thunderstorm']) => '1,3,4,2,5',
            in_array($cuaca, ['clear'])                           => '2,5,4,1,3',
            in_array($cuaca, ['clouds'])                          => '1,2,5,3,4',
            default                                               => '1,2,3,4,5',
        };
    }

    /**
     * Tentukan level peringatan & pesan berdasarkan cuaca dan suhu.
     */
    private function levelCuaca(string $cuaca, int $suhu): array
    {
        if (in_array($cuaca, ['thunderstorm'])) {
            return [
                'level' => 'danger',
                'pesan' => 'Potensi badai petir! Pastikan tenda sangat kuat dan hindari puncak terbuka.',
                'warna' => 'red',
            ];
        }

        if (in_array($cuaca, ['rain', 'drizzle'])) {
            return [
                'level' => 'warning',
                'pesan' => 'Hujan diprakirakan. Siapkan tenda waterproof dan jas hujan.',
                'warna' => 'amber',
            ];
        }

        if ($suhu <= 10) {
            return [
                'level' => 'warning',
                'pesan' => 'Suhu sangat dingin! Sleeping bag tebal sangat disarankan.',
                'warna' => 'blue',
            ];
        }

        if (in_array($cuaca, ['clear', 'clouds'])) {
            return [
                'level' => 'good',
                'pesan' => 'Cuaca cukup baik untuk camping. Tetap persiapkan perlengkapan lengkap.',
                'warna' => 'green',
            ];
        }

        return [
            'level' => 'neutral',
            'pesan' => 'Kondisi cuaca bervariasi. Disarankan membawa perlengkapan lengkap.',
            'warna' => 'gray',
        ];
    }
}
