<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehousesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('catalogs/categories', [CategoryController::class, 'options'])->name('api.catalogs.categories');
    Route::get('catalogs/suppliers', [SupplierController::class, 'options'])->name('api.catalogs.suppliers');
    Route::get('catalogs/products', [ProductController::class, 'options'])->name('api.catalogs.products');

    Route::get('people/identities', [IdentityController::class, 'options'])->name('api.people.identities');

    Route::get('warehouses', [WarehousesController::class, 'options'])->name('api.warehouses');
});
