<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('app.dashboard');

    Route::get('/kasir', [DashboardController::class, 'kasir'])
        ->name('app.kasir');

    Route::get('/produk', [DashboardController::class, 'produk'])
        ->name('app.produk');

    Route::get('/kategori', [DashboardController::class, 'kategori'])
        ->name('app.kategori');

    Route::get('/stok', [DashboardController::class, 'stok'])
        ->name('app.stok');

    Route::get('/transaksi', [DashboardController::class, 'transaksi'])
        ->name('app.transaksi');

    Route::get('/laporan', [DashboardController::class, 'laporan'])
        ->name('app.laporan');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    // API routes
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'store']);
});
