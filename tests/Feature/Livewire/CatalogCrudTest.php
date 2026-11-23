<?php

use App\Livewire\Modules\catalogs\Categories\Index as CategoryIndex;
use App\Livewire\Modules\catalogs\Products\Form as ProductForm;
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

test('a product can be created and edited with calculated commercial fields', function () {
    $category = Category::create(['name' => 'Papelería', 'porcent' => 20]);
    $supplier = Supplier::create(['name' => 'Proveedor central']);

    Livewire::test(ProductForm::class)
        ->set('categoryId', $category->id)
        ->set('supplierId', $supplier->id)
        ->set('name', 'Papel adhesivo')
        ->set('description', 'Paquete de prueba')
        ->set('sku', 'PA-001')
        ->set('barcode', '750000000001')
        ->set('applyIva', true)
        ->set('costPackage', '802.5000')
        ->set('unitsPackage', '10.000')
        ->set('minStock', '2.000')
        ->set('isLayawayAllowed', true)
        ->set('isReturnable', true)
        ->set('isActive', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('products.index'));

    $product = Product::where('sku', 'PA-001')->firstOrFail();

    expect($product->cost)->toBe('80.2500')
        ->and($product->cost_iva)->toBe('93.0900')
        ->and($product->price)->toBe('116.36')
        ->and($product->porcent)->toBe(20);

    Livewire::test(ProductForm::class, ['product' => $product])
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

    Livewire::test(ProductForm::class)
        ->set('categoryId', 999999)
        ->set('name', 'Duplicado')
        ->set('sku', 'SKU-UNICO')
        ->call('submit')
        ->assertHasErrors(['categoryId' => 'exists', 'sku' => 'unique']);
});

test('a category percentage is prefilled and can be overridden', function () {
    $category = Category::create(['name' => 'Margen especial', 'porcent' => 25]);

    Livewire::test(ProductForm::class)
        ->set('categoryId', $category->id)
        ->assertSet('porcent', 25)
        ->set('porcent', 30)
        ->assertSet('porcent', 30);
});

test('product price calculations support products without iva', function () {
    Livewire::test(ProductForm::class)
        ->set('cost', '75.0000')
        ->set('porcent', 25)
        ->set('applyIva', false)
        ->assertSet('costIva', '75.00')
        ->assertSet('price', '100.00');
});

test('product margin and package units are validated', function () {
    Livewire::test(ProductForm::class)
        ->set('costPackage', '100')
        ->set('unitsPackage', '0')
        ->set('porcent', 100)
        ->call('submit')
        ->assertHasErrors(['unitsPackage' => 'gt', 'porcent' => 'lt']);

    Livewire::test(ProductForm::class)
        ->set('unitsPackage', '10')
        ->call('submit')
        ->assertHasErrors(['costPackage' => 'required_with']);
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
