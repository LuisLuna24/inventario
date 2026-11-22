<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('catalogs/categories', [CategoryController::class, 'options'])->name('api.catalogs.categories');
    Route::get('catalogs/suppliers', [SupplierController::class, 'options'])->name('api.catalogs.suppliers');
});
