<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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

        $fotoUrl   = $fotoUtama
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
     * Buat URL publik dari path di storage/app/public.
     * Memakai asset() agar Intelephense tidak komplain soal url().
     */
    private function storageUrl(string $path): string
    {
        // asset('storage/...') = sama persis dengan Storage::disk('public')->url(...)
        return asset('storage/' . ltrim($path, '/'));
    }
}
