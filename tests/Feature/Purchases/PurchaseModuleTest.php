<?php

use App\Exceptions\ExcessPurchaseQuantityException;
use App\Livewire\Modules\Purchases\Form as PurchaseForm;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Purchases\PurchaseTotals;
use App\Services\Purchases\ReceivePurchase;
use Livewire\Livewire;

function purchaseProduct(array $attributes = []): Product
{
    $category = Category::create(['name' => 'Compras '.fake()->unique()->word(), 'porcent' => 0]);

    return Product::create(array_merge([
        'category_id' => $category->id,
        'name' => 'Producto '.fake()->unique()->word(),
        'price' => 30,
        'cost' => 10,
        'cost_iva' => 11.60,
        'apply_iva' => true,
        'min_stock' => 1,
        'porcent' => 0,
        'is_active' => true,
    ], $attributes));
}

/** @return array{0: Purchase, 1: Supplier, 2: Warehouse} */
function draftPurchase(array $attributes = []): array
{
    $supplier = Supplier::create(['name' => 'Proveedor '.fake()->unique()->word()]);
    $warehouse = Warehouse::create(['name' => 'Almacén '.fake()->unique()->word()]);
    $purchase = Purchase::create(array_merge([
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'voucher_type' => 'purchase',
        'serie' => 'COMP',
        'correlative' => fake()->unique()->numerify('######'),
        'date' => now(),
        'subtotal' => 0,
        'tax_total' => 0,
        'total' => 0,
        'status' => Purchase::STATUS_DRAFT,
    ], $attributes));

    return [$purchase, $supplier, $warehouse];
}

test('purchase totals apply iva only to products configured with iva', function () {
    $taxed = purchaseProduct(['name' => 'Con IVA', 'apply_iva' => true]);
    $untaxed = purchaseProduct(['name' => 'Sin IVA', 'apply_iva' => false]);

    $totals = app(PurchaseTotals::class)->calculate([
        ['product_id' => $taxed->id, 'unit_type' => 'piece', 'quantity' => 2, 'unit_cost' => 100],
        ['product_id' => $untaxed->id, 'unit_type' => 'piece', 'quantity' => 1, 'unit_cost' => 50],
    ]);

    expect($totals['subtotal'])->toBe(250.0)
        ->and($totals['tax_total'])->toBe(32.0)
        ->and($totals['total'])->toBe(282.0)
        ->and($totals['items'][0]['tax_amount'])->toBe(32.0)
        ->and($totals['items'][1]['tax_amount'])->toBe(0.0);
});

test('the purchase form stores a draft with server calculated totals', function () {
    $supplier = Supplier::create(['name' => 'Proveedor de formulario']);
    $warehouse = Warehouse::create(['name' => 'Almacén de formulario']);
    $product = purchaseProduct(['name' => 'Producto del formulario', 'apply_iva' => true]);

    Livewire::actingAs(User::factory()->create())
        ->test(PurchaseForm::class)
        ->set('supplierId', $supplier->id)
        ->set('warehouseId', $warehouse->id)
        ->set('date', now()->format('Y-m-d'))
        ->set('lines', [[
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_type' => PurchaseItem::UNIT_PIECE,
            'quantity' => '2',
            'unit_cost' => '100',
        ]])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('purchases.index'));

    $purchase = Purchase::firstOrFail();

    expect($purchase->status)->toBe(Purchase::STATUS_DRAFT)
        ->and($purchase->serie)->toBe('COMP')
        ->and($purchase->correlative)->toBe('000001')
        ->and($purchase->subtotal)->toBe('200.00')
        ->and($purchase->tax_total)->toBe('32.00')
        ->and($purchase->total)->toBe('232.00')
        ->and($purchase->items)->toHaveCount(1);
});

test('authenticated users can open the purchase list and create form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('purchases.index'))
        ->assertOk()
        ->assertSee('Nueva compra');

    $this->actingAs($user)
        ->get(route('purchases.create'))
        ->assertOk()
        ->assertSee('Guardar borrador');
});

test('confirming a box purchase converts boxes to pieces and updates inventory cost', function () {
    [$purchase, , $warehouse] = draftPurchase();
    $product = purchaseProduct([
        'cost' => 5,
        'cost_iva' => 5.80,
        'cost_package' => 60,
        'units_package' => 12,
    ]);
    $item = $purchase->items()->create([
        'product_id' => $product->id,
        'unit_type' => PurchaseItem::UNIT_PACKAGE,
        'units_per_package' => 12,
        'quantity' => 2,
        'unit_cost' => 240,
        'line_total' => 480,
        'tax_rate' => 0.16,
        'tax_amount' => 76.80,
    ]);
    $user = User::factory()->create();

    app(ReceivePurchase::class)->handle($purchase, $user);

    $balance = StockBalance::where('warehouse_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->firstOrFail();
    $movement = Inventory::where('purchase_item_id', $item->id)->firstOrFail();

    expect($balance->physical_quantity)->toBe('24.000')
        ->and($movement->quantity_in)->toBe('24.000')
        ->and($movement->cost_in)->toBe('20.0000')
        ->and($movement->total_in)->toBe('480.00')
        ->and($product->refresh()->cost)->toBe('20.0000')
        ->and($product->cost_iva)->toBe('23.2000')
        ->and($purchase->refresh()->status)->toBe(Purchase::STATUS_RECEIVED)
        ->and($purchase->confirmed_by)->toBe($user->id);
});

test('confirming a purchase twice does not duplicate stock or movements', function () {
    [$purchase, , $warehouse] = draftPurchase();
    $product = purchaseProduct();
    $purchase->items()->create([
        'product_id' => $product->id,
        'unit_type' => PurchaseItem::UNIT_PIECE,
        'quantity' => 3,
        'unit_cost' => 10,
        'line_total' => 30,
        'tax_rate' => 0.16,
        'tax_amount' => 4.80,
    ]);
    $user = User::factory()->create();
    $service = app(ReceivePurchase::class);

    $service->handle($purchase, $user);
    $service->handle($purchase->refresh(), $user);

    expect(Inventory::whereHas('purchaseItem', fn ($query) => $query->where('purchase_id', $purchase->id))->count())->toBe(1)
        ->and(StockBalance::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('physical_quantity'))->toBe('3.000');
});

test('an order excess requires explicit confirmation and then marks the order purchased', function () {
    [$purchase, $supplier, $warehouse] = draftPurchase();
    $product = purchaseProduct();
    $order = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'voucher_type' => 1,
        'serie' => 'OC',
        'correlative' => '000001',
        'date' => now(),
        'total' => 50,
        'status' => 'pending',
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_cost' => 10,
        'line_total' => 50,
    ]);
    $purchase->update(['purchase_order_id' => $order->id]);
    $purchase->items()->create([
        'product_id' => $product->id,
        'unit_type' => PurchaseItem::UNIT_PIECE,
        'quantity' => 6,
        'unit_cost' => 10,
        'line_total' => 60,
        'tax_rate' => 0.16,
        'tax_amount' => 9.60,
    ]);
    $user = User::factory()->create();
    $service = app(ReceivePurchase::class);

    expect(fn () => $service->handle($purchase, $user))->toThrow(ExcessPurchaseQuantityException::class);
    expect(Inventory::count())->toBe(0)
        ->and($purchase->refresh()->status)->toBe(Purchase::STATUS_DRAFT);

    $service->handle($purchase, $user, true);

    expect($order->refresh()->status)->toBe(PurchaseOrder::STATUS_PURCHASED)
        ->and(StockBalance::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->value('physical_quantity'))->toBe('6.000');
});

test('the complete inventory operation rolls back when one item fails', function () {
    [$purchase] = draftPurchase();
    $firstProduct = purchaseProduct(['name' => 'Producto válido', 'cost' => 7]);
    $brokenProduct = purchaseProduct(['name' => 'Producto inválido', 'cost' => 9]);
    $purchase->items()->create([
        'product_id' => $firstProduct->id,
        'unit_type' => PurchaseItem::UNIT_PIECE,
        'quantity' => 2,
        'unit_cost' => 15,
        'line_total' => 30,
        'tax_rate' => 0.16,
        'tax_amount' => 4.80,
    ]);
    $purchase->items()->create([
        'product_id' => $brokenProduct->id,
        'unit_type' => PurchaseItem::UNIT_PACKAGE,
        'units_per_package' => 0,
        'quantity' => 1,
        'unit_cost' => 20,
        'line_total' => 20,
        'tax_rate' => 0.16,
        'tax_amount' => 3.20,
    ]);

    expect(fn () => app(ReceivePurchase::class)->handle($purchase, User::factory()->create()))
        ->toThrow(DivisionByZeroError::class);

    expect(Inventory::count())->toBe(0)
        ->and(StockBalance::count())->toBe(0)
        ->and($firstProduct->refresh()->cost)->toBe('7.0000')
        ->and($purchase->refresh()->status)->toBe(Purchase::STATUS_DRAFT);
});
