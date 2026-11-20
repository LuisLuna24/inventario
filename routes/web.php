<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehousesController;
use App\Models\Customer;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // 1. SUCURSALES
    Route::resource('almacenes', WarehousesController::class)->only(['index', 'create', 'edit']);

    // 2. USUARIOS
    Route::resource('empleados', EmployeesController::class)->only(['index', 'create', 'edit']);
    Route::resource('roles', RoleController::class)->only(['index', 'create', 'edit']);
    Route::resource('permisos', PermissionController::class)->only(['index', 'create', 'edit']);

    // 3. CLIENTES Y PROVEEDORES
    Route::resource('clientes', CustomerController::class)->only(['index', 'create', 'edit']);
    Route::resource('proveedores', SupplierController::class)->only(['index', 'create', 'edit']);

    // 4. CATALOGO DE PRODUCTOS
    Route::resource('categorias', CategoryController::class)->only(['index', 'create', 'edit']);
    Route::resource('productos', ProductController::class)->only(['index', 'create', 'edit']);
});

require __DIR__ . '/settings.php';
