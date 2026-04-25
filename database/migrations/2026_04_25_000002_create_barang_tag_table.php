<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot table `barang_tag` — relasi many-to-many antara barang dan tag.
 *
 * Satu barang bisa punya banyak tag:
 *   - Tenda  → [shelter, waterproof]
 *   - Jas hujan → [waterproof]
 *   - Sleeping bag → [insulating, shelter]
 *   - Kompor → [cooking]
 *   - Sepatu gunung → [footwear, waterproof]
 *
 * Admin bisa assign tag ke barang langsung dari panel admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_tag', function (Blueprint $table) {
            $table->id();

            $table->foreignId('barang_id')
                ->constrained('barang')
                ->onDelete('cascade');

            $table->foreignId('tag_id')
                ->constrained('tags')
                ->onDelete('cascade');

            // Satu barang tidak boleh punya tag yang sama dua kali
            $table->unique(['barang_id', 'tag_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_tag');
    }
};
