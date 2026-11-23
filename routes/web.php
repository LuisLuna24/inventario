<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehousesController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // 1. WAREHOUSES
    Route::resource('warehouses', WarehousesController::class)->only(['index']);

    // 2. USERS
    Route::resource('employees', EmployeesController::class)->only(['index', 'create', 'edit']);
    Route::resource('roles', RoleController::class)->only(['index', 'create', 'edit']);
    Route::resource('permissions', PermissionController::class)->only(['index', 'create', 'edit']);

    // 3. CUSTOMERS AND SUPPLIERS
    Route::resource('customers', CustomerController::class)->only(['index', 'create', 'edit']);
    Route::resource('suppliers', SupplierController::class)->only(['index', 'create', 'edit']);
    Route::resource('identities', IdentityController::class)->only(['index', 'create', 'edit']);

    // 4. PRODUCT CATALOG
    Route::resource('categories', CategoryController::class)->only(['index']);
    Route::resource('products', ProductController::class)->only(['index', 'create', 'edit']);
});

require __DIR__.'/settings.php';
