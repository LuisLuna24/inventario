<?php

use App\Livewire\Modules\catalogs\Categories\Index as CategoryIndex;
use App\Livewire\Modules\catalogs\Products\Index as ProductIndex;
use App\Livewire\Modules\catalogs\Warehouses\Index as WarehouseIndex;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Warehouse;
use Livewire\Livewire;

test('a category can be created and edited', function () {
    Livewire::test(CategoryIndex::class)
        ->set('name', 'Consumibles')
        ->set('description', 'Material de uso diario')
        ->set('porcent', 12)
        ->call('submit')
        ->assertHasNoErrors();

    $category = Category::where('name', 'Consumibles')->firstOrFail();

    Livewire::test(CategoryIndex::class)
        ->call('edit', $category->id)
        ->set('name', 'Consumibles generales')
        ->call('submit')
        ->assertHasNoErrors();

    expect($category->refresh()->name)->toBe('Consumibles generales');
});

test('category fields are validated', function () {
    Livewire::test(CategoryIndex::class)
        ->set('name', '')
        ->set('porcent', -1)
        ->call('submit')
        ->assertHasErrors(['name' => 'required', 'porcent' => 'min']);
});

test('a category with products cannot be deleted', function () {
    $category = Category::create(['name' => 'Viniles', 'porcent' => 0]);
    Product::create([
        'category_id' => $category->id,
        'name' => 'Vinil blanco',
        'price' => 10,
        'cost' => 5,
        'min_stock' => 1,
        'porcent' => 0,
    ]);

    Livewire::test(CategoryIndex::class)->call('delete', $category->id);

    expect($category->fresh())->not->toBeNull();
});

test('a product can be created and edited with all commercial fields', function () {
    $category = Category::create(['name' => 'Papelería', 'porcent' => 0]);
    $supplier = Supplier::create(['name' => 'Proveedor central']);

    Livewire::test(ProductIndex::class)
        ->set('categoryId', $category->id)
        ->set('supplierId', $supplier->id)
        ->set('name', 'Papel adhesivo')
        ->set('description', 'Paquete de prueba')
        ->set('sku', 'PA-001')
        ->set('barcode', '750000000001')
        ->set('price', '120.50')
        ->set('cost', '80.2500')
        ->set('applyIva', true)
        ->set('costPackage', '802.5000')
        ->set('unitsPackage', '10.000')
        ->set('minStock', '2.000')
        ->set('porcent', 20)
        ->set('isLayawayAllowed', true)
        ->set('isReturnable', true)
        ->set('isActive', true)
        ->call('submit')
        ->assertHasNoErrors();

    $product = Product::where('sku', 'PA-001')->firstOrFail();

    Livewire::test(ProductIndex::class)
        ->call('edit', $product->id)
        ->set('name', 'Papel adhesivo premium')
        ->call('submit')
        ->assertHasNoErrors();

    expect($product->refresh()->name)->toBe('Papel adhesivo premium')
        ->and($product->supplier_id)->toBe($supplier->id);
});

test('product relationships and unique identifiers are validated', function () {
    $category = Category::create(['name' => 'Existente', 'porcent' => 0]);
    Product::create([
        'category_id' => $category->id,
        'name' => 'Producto existente',
        'sku' => 'SKU-UNICO',
        'price' => 1,
        'cost' => 1,
        'min_stock' => 1,
        'porcent' => 0,
    ]);

    Livewire::test(ProductIndex::class)
        ->set('categoryId', 999999)
        ->set('name', 'Duplicado')
        ->set('sku', 'SKU-UNICO')
        ->call('submit')
        ->assertHasErrors(['categoryId' => 'exists', 'sku' => 'unique']);
});

test('a product with stock cannot be deleted', function () {
    $category = Category::create(['name' => 'Inventariable', 'porcent' => 0]);
    $warehouse = Warehouse::create(['name' => 'Central']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Producto con stock',
        'price' => 1,
        'cost' => 1,
        'min_stock' => 1,
        'porcent' => 0,
    ]);
    StockBalance::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'physical_quantity' => 5,
        'reserved_quantity' => 0,
    ]);

    Livewire::test(ProductIndex::class)->call('delete', $product->id);

    expect($product->fresh())->not->toBeNull();
});

test('a warehouse can be created and edited', function () {
    Livewire::test(WarehouseIndex::class)
        ->set('name', 'Almacén norte')
        ->set('location', 'Zona norte')
        ->set('isActive', true)
        ->call('submit')
        ->assertHasNoErrors();

    $warehouse = Warehouse::where('name', 'Almacén norte')->firstOrFail();

    Livewire::test(WarehouseIndex::class)
        ->call('edit', $warehouse->id)
        ->set('location', 'Zona industrial')
        ->call('submit')
        ->assertHasNoErrors();

    expect($warehouse->refresh()->location)->toBe('Zona industrial');
});

test('unrelated catalog records can be deleted', function () {
    $category = Category::create(['name' => 'Temporal', 'porcent' => 0]);
    $warehouse = Warehouse::create(['name' => 'Temporal']);

    Livewire::test(CategoryIndex::class)->call('delete', $category->id);
    Livewire::test(WarehouseIndex::class)->call('delete', $warehouse->id);

    expect($category->fresh())->toBeNull()
        ->and($warehouse->fresh())->toBeNull();
});
