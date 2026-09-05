<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryReportController;

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

    Route::get('/inventory', [DashboardController::class, 'inventory'])
        ->name('app.inventory');

    Route::get('/resep', [DashboardController::class, 'resep'])
        ->name('app.resep');

    Route::get('/users', [DashboardController::class, 'users'])
        ->name('app.users')
        ->middleware('admin');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    // API routes - admin only
    Route::middleware('admin')->group(function () {
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::get('stock-movements', [StockMovementController::class, 'index']);
        Route::apiResource('users', UserController::class);

        Route::apiResource('ingredients', IngredientController::class);
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('recipes', RecipeController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::get('recipes/menu/{product}', [RecipeController::class, 'menu']);

        Route::get('inventory/summary', [InventoryController::class, 'summary']);
        Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);
        Route::get('inventory/movements', [InventoryController::class, 'movements']);

        Route::get('stock-in', [StockInController::class, 'index']);
        Route::post('stock-in', [StockInController::class, 'store']);
        Route::get('stock-out', [StockOutController::class, 'index']);
        Route::post('stock-out', [StockOutController::class, 'store']);
        Route::get('stock-adjustments', [StockAdjustmentController::class, 'index']);
        Route::post('stock-adjustments', [StockAdjustmentController::class, 'store']);

        Route::get('reports/inventory', [InventoryReportController::class, 'report']);
        Route::get('reports/inventory/recipes', [InventoryReportController::class, 'recipeReport']);
    });

    // Kasir can view transactions and create them
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'store']);

    // Transaction receipt
    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])
        ->name('transactions.receipt');

    // Report exports
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])
        ->name('reports.export-pdf');
    Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])
        ->name('reports.export-csv');
});
