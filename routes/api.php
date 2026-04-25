<?php

use App\Http\Controllers\CuacaController;
use Illuminate\Support\Facades\Route;

// --- Cuaca & Lokasi ---
Route::get('/cuaca',          [CuacaController::class, 'cek']);
Route::get('/lokasi/cari',    [CuacaController::class, 'cariLokasi']);
Route::get('/lokasi/reverse', [CuacaController::class, 'reverseLokasi']);
