<?php

namespace App\Http\Controllers;

use App\Services\WeatherRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CuacaController extends Controller
{
    public function __construct(
        private readonly WeatherRecommendationService $rekomendasiService
    ) {}

    // =========================================================================
    //  ENDPOINT 1 — Geocoding / Search Lokasi
    //  GET /api/lokasi/cari?q={keyword}
    //
    //  Menggunakan Nominatim OpenStreetMap — GRATIS, tanpa API key.
    //  Rate limit Nominatim: 1 req/detik (cukup untuk use-case ini).
    // =========================================================================

    /**
     * Cari lokasi via Nominatim (OpenStreetMap).
     * Mengembalikan max 6 hasil dengan nama, lat, lon, tipe lokasi.
     */
    public function cariLokasi(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        $query = trim($request->q);

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    // Nominatim mensyaratkan User-Agent yang jelas
                    'User-Agent' => 'MajelisRental/1.0 (rental@majelis.id)',
                    'Accept-Language' => 'id,en',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'              => $query,
                    'format'         => 'json',
                    'addressdetails' => 1,
                    'limit'          => 6,
                    'countrycodes'   => 'id', // Fokus Indonesia, hapus jika ingin global
                ]);

            if ($response->failed()) {
                return response()->json(['message' => 'Gagal menghubungi layanan pencarian.'], 502);
            }

            $hasil = collect($response->json())
                ->map(fn($item) => [
                    'nama'      => $item['display_name'],
                    'nama_pendek' => $this->namaLokasiPendek($item),
                    'provinsi'  => $item['address']['state'] ?? $item['address']['province'] ?? '',
                    'lat'       => (float) $item['lat'],
                    'lon'       => (float) $item['lon'],
                    'tipe'      => $item['type'] ?? $item['class'] ?? 'place',
                    'osm_id'    => $item['osm_id'],
                ])
                ->values();

            return response()->json(['hasil' => $hasil]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan pencarian: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reverse geocoding: dari koordinat → nama lokasi.
     * GET /api/lokasi/reverse?lat={lat}&lon={lon}
     */
    public function reverseLokasi(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent'     => 'MajelisRental/1.0 (rental@majelis.id)',
                    'Accept-Language' => 'id,en',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat'    => $request->lat,
                    'lon'    => $request->lon,
                    'format' => 'json',
                ]);

            if ($response->failed()) {
                return response()->json(['message' => 'Gagal reverse geocoding.'], 502);
            }

            $data = $response->json();

            return response()->json([
                'nama'       => $data['display_name'] ?? 'Lokasi tidak diketahui',
                'nama_pendek' => $this->namaLokasiPendek($data),
                'provinsi'   => $data['address']['state'] ?? $data['address']['province'] ?? '',
                'lat'        => (float) $request->lat,
                'lon'        => (float) $request->lon,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    //  ENDPOINT 2 — Cek Cuaca
    //  GET /api/cuaca?lat={lat}&lon={lon}&nama_lokasi={string}&tanggal_ambil={Y-m-d}
    // =========================================================================

    /**
     * Ambil data cuaca & rekomendasi barang berdasarkan koordinat bebas.
     */
    public function cek(Request $request): JsonResponse
    {
        $request->validate([
            'lat'           => ['required', 'numeric', 'between:-90,90'],
            'lon'           => ['required', 'numeric', 'between:-180,180'],
            'nama_lokasi'   => ['nullable', 'string', 'max:200'],
            'tanggal_ambil' => ['required', 'date'],
        ]);

        // OWM hanya support forecast 5 hari ke depan
        if ($request->tanggal_ambil > now()->addDays(5)->toDateString()) {
            return response()->json([
                'lokasi'      => $request->nama_lokasi ?? 'Lokasi pilihan',
                'provinsi'    => '',
                'cuaca'       => null,
                'rekomendasi' => [],
                'message'     => 'Prediksi cuaca hanya tersedia hingga 5 hari ke depan.',
            ]);
        }

        $apiKey = config('services.openweather.key');

        if (!$apiKey) {
            return response()->json(['message' => 'API key cuaca belum dikonfigurasi.'], 500);
        }

        try {
            $forecast = $this->fetchForecast(
                lat: (float) $request->lat,
                lon: (float) $request->lon,
                tanggalAmbil: $request->tanggal_ambil,
                apiKey: $apiKey,
            );

            if (!$forecast) {
                return response()->json([
                    'lokasi'      => $request->nama_lokasi ?? 'Lokasi pilihan',
                    'provinsi'    => '',
                    'cuaca'       => null,
                    'rekomendasi' => [],
                    'message'     => 'Data forecast tidak tersedia untuk lokasi ini.',
                ]);
            }

            $condition  = strtolower($forecast['weather'][0]['main'] ?? 'default');
            $deskripsi  = ucfirst($forecast['weather'][0]['description'] ?? '-');
            $suhu       = (int) round($forecast['main']['temp'] ?? 0);
            $suhuMin    = (int) round($forecast['main']['temp_min'] ?? 0);
            $suhuMax    = (int) round($forecast['main']['temp_max'] ?? 0);
            $kelembaban = $forecast['main']['humidity'] ?? 0;
            $angin      = round(($forecast['wind']['speed'] ?? 0) * 3.6, 1);
            $iconCode   = $forecast['weather'][0]['icon'] ?? '01d';

            $level       = $this->rekomendasiService->getLevelCuaca($condition, $suhu);
            $rekomendasi = $this->rekomendasiService->getRekomendasi($condition);

            return response()->json([
                'lokasi'   => $request->nama_lokasi ?? 'Lokasi pilihan',
                'provinsi' => '',
                'cuaca'    => [
                    'main'       => $condition,
                    'deskripsi'  => $deskripsi,
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
                'rekomendasi' => $rekomendasi,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    //  PRIVATE HELPERS
    // =========================================================================

    /**
     * Ambil nama pendek & enak dibaca dari data Nominatim.
     * Prioritas: gunung/village/town/city, lalu fallback display_name pendek.
     */
    private function namaLokasiPendek(array $item): string
    {
        $addr = $item['address'] ?? [];

        // Coba field yang paling spesifik dulu
        foreach (['peak', 'natural', 'tourism', 'leisure', 'village', 'town', 'city_district', 'suburb', 'city', 'county'] as $field) {
            if (!empty($addr[$field])) {
                $provinsi = $addr['state'] ?? $addr['province'] ?? '';
                return $addr[$field] . ($provinsi ? ", {$provinsi}" : '');
            }
        }

        // Fallback: ambil 2 bagian pertama dari display_name
        $parts = explode(', ', $item['display_name'] ?? '');
        return implode(', ', array_slice($parts, 0, 2));
    }

    /**
     * Fetch forecast OWM untuk koordinat & tanggal tertentu.
     */
    private function fetchForecast(float $lat, float $lon, string $tanggalAmbil, string $apiKey): ?array
    {
        $response = Http::timeout(8)->get('https://api.openweathermap.org/data/2.5/forecast', [
            'lat'   => $lat,
            'lon'   => $lon,
            'appid' => $apiKey,
            'units' => 'metric',
            'lang'  => 'id',
            'cnt'   => 8,
        ]);

        if ($response->failed()) {
            return null;
        }

        $forecastList = $response->json('list', []);

        if (empty($forecastList)) {
            return null;
        }

        foreach ($forecastList as $item) {
            if (date('Y-m-d', $item['dt']) === $tanggalAmbil) {
                return $item;
            }
        }

        return $forecastList[0];
    }
}
