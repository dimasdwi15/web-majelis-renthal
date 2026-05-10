<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecommendationResource extends JsonResource
{
    private int   $matchScore;
    private array $matchedTags;

    public function __construct($resource, int $matchScore = 0, array $matchedTags = [])
    {
        parent::__construct($resource);
        $this->matchScore  = $matchScore;
        $this->matchedTags = $matchedTags;
    }

    public function toArray(Request $request): array
    {
        $fotoUtama = $this->fotos?->first();

        $fotoUrl = $fotoUtama
            ? $this->storageUrl($fotoUtama->path_foto)
            : null;

        $semuaFoto = $this->fotos
            ? $this->fotos->map(fn($f) => $this->storageUrl($f->path_foto))->values()
            : collect();

        return [
            'id'             => $this->id,
            'nama'           => $this->nama,
            'deskripsi'      => $this->deskripsi,
            'spesifikasi'    => $this->spesifikasi,
            'harga_per_hari' => (float) $this->harga_per_hari,
            'stok'           => $this->stok,
            'status'         => $this->status,
            'foto_url'       => $fotoUrl,
            'semua_foto'     => $semuaFoto,
            'kategori'       => $this->whenLoaded('kategori', fn() => $this->kategori ? [
                'id'   => $this->kategori->id,
                'nama' => $this->kategori->nama,
            ] : null),
            'tags' => $this->whenLoaded('tags', fn() =>
                $this->tags->map(fn($t) => [
                    'slug'  => $t->slug,
                    'label' => $t->label,
                ])->values()
            ),
            'match_score'  => $this->matchScore,
            'matched_tags' => $this->matchedTags,
        ];
    }

    /**
     * Build public URL dari path storage — mengikuti host request saat ini.
     *
     * FIX: Dulu pakai asset() yang membaca APP_URL (.env = localhost),
     * sehingga URL gambar selalu mengarah ke localhost meski diakses via ngrok.
     *
     * Solusi: pakai request()->getSchemeAndHttpHost() agar URL mengikuti
     * host aktual (ngrok / IP lokal / domain production).
     */
    private function storageUrl(string $path): string
    {
        $host = request()->getSchemeAndHttpHost();
        return $host . '/storage/' . ltrim($path, '/');
    }
}
