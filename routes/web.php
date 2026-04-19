<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\MidtransCallbackController;

use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\PesananController;
use App\Http\Controllers\User\ProfilController;
use App\Http\Controllers\User\NotifikasiController;

Route::get('/', fn() => view('home'))->name('home');

Route::get('/dashboard', fn() => view('dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public pages
Route::get('/tentang-kami', fn() => view('user.pages.about'))->name('about');

// Katalog
Route::get('/katalog', [KatalogController::class, 'index'])->name('katalog');

// Keranjang — session-based, tidak perlu login
Route::prefix('keranjang')->name('keranjang.')->group(function () {
    Route::get('/',                    [KeranjangController::class, 'index'])->name('index');
    Route::post('/tambah/{barang}',    [KeranjangController::class, 'tambah'])->name('tambah');
    Route::patch('/update/{barangId}', [KeranjangController::class, 'update'])->name('update');
    Route::delete('/hapus/{barangId}', [KeranjangController::class, 'hapus'])->name('hapus');
    Route::delete('/kosongkan',        [KeranjangController::class, 'kosongkan'])->name('kosongkan');
    Route::get('/refresh',             [KeranjangController::class, 'refresh'])->name('refresh');
});

// Checkout (harus login)
Route::middleware(['auth'])->group(function () {
    Route::get('/checkout',                              [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/proses',                     [CheckoutController::class, 'proses'])->name('checkout.proses');
    Route::get('/checkout/sukses/{nomorTransaksi}',     [CheckoutController::class, 'sukses'])->name('checkout.sukses');
});

// USER AREA
Route::middleware(['auth'])->prefix('akun')->name('user.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pesanan — letakkan route statis SEBELUM route param dinamis
    Route::prefix('pesanan')->name('pesanan.')->group(function () {
        Route::get('/',                            [PesananController::class, 'index'])->name('index');
        // Bayar denda (statis dulu)
        Route::get('/bayar-denda/{denda}',         [PesananController::class, 'bayarDenda'])->name('bayar-denda');
        Route::post('/bayar-denda/{denda}',        [PesananController::class, 'prosesBayarDenda'])->name('proses-bayar-denda');
        // Detail transaksi (dinamis setelah statis)
        Route::get('/{transaksi}',                 [PesananController::class, 'show'])->name('show');
    });

    // Profil
    Route::prefix('profil')->name('profil.')->group(function () {
        Route::get('/edit',              [ProfilController::class, 'edit'])->name('edit');
        Route::patch('/update',          [ProfilController::class, 'update'])->name('update');
        Route::patch('/update-password', [ProfilController::class, 'updatePassword'])->name('update-password');
        Route::delete('/destroy',        [ProfilController::class, 'destroy'])->name('destroy');
    });

    // Notifikasi
    Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
        Route::get('/',                    [NotifikasiController::class, 'index'])->name('index');
        Route::post('/baca-semua',         [NotifikasiController::class, 'bacaSemua'])->name('baca-semua');
        Route::patch('/{notifikasi}/baca', [NotifikasiController::class, 'baca'])->name('baca');
    });
});

Route::get('/pesanan/{transaksi}/struk', [PesananController::class, 'struk'])
    ->name('user.pesanan.struk');

// Midtrans webhook callback (no CSRF needed — excluded in bootstrap/app.php)
Route::post('/midtrans/callback', [MidtransCallbackController::class, 'handle'])
    ->name('midtrans.callback');

Route::post('/pesanan/{transaksi}/bayar-ulang', [PesananController::class, 'bayarUlang'])
    ->middleware('auth')
    ->name('user.pesanan.bayar-ulang');

Route::post('/bayar-denda/{denda}', [PesananController::class, 'bayarDendaLangsung'])
    ->middleware('auth');
require __DIR__ . '/auth.php';
