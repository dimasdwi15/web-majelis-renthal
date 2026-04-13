<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('barang_foto')
            ->where('path_foto', 'NOT LIKE', 'barang/%')
            ->update([
                'path_foto' => DB::raw("CONCAT('barang/', path_foto)")
            ]);
    }

    public function down(): void
    {
        DB::table('barang_foto')
            ->where('path_foto', 'LIKE', 'barang/%')
            ->update([
                'path_foto' => DB::raw("REPLACE(path_foto, 'barang/', '')")
            ]);
    }
};
