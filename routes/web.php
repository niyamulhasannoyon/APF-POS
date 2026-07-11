<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SyncController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'bn'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('lang.swap');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin Panel Subgroup
    Route::prefix('admin')->name('admin.')->group(function () {
        // Branches
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');

        // Products & Stock Adjustments
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::post('/products/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');

        // Customers
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

        // Orders / Sales History
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    });

    // POS Cashier View
    Route::get('/pos', function () {
        return view('pos.index');
    })->name('pos.index');
});

require __DIR__.'/auth.php';
