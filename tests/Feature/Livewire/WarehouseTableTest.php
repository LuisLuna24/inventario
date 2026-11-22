<?php

use App\Livewire\Tables\Warehouse\WarehouseTable;
use App\Models\Warehouse;
use Livewire\Livewire;

test('table renders the native powergrid toggle', function () {
    Warehouse::create([
        'name' => 'Almacén central',
        'is_active' => true,
    ]);

    Livewire::test(WarehouseTable::class)
        ->assertSee('pg-toggleable-switch', escape: false)
        ->assertSee('&quot;toggle&quot;:1', escape: false);
});

test('warehouse can be activated by the powergrid toggle', function () {
    $warehouse = Warehouse::create([
        'name' => 'Almacén central',
        'is_active' => false,
    ]);

    Livewire::test(WarehouseTable::class)
        ->dispatch(
            'pg:toggleable-warehouseTable',
            'is_active_toggle',
            (string) $warehouse->id,
            '1',
        )
        ->assertDispatched('toast-show')
        ->assertSee('&quot;toggle&quot;:1', escape: false);

    expect($warehouse->refresh()->is_active)->toBeTrue();
});

test('warehouse can be deactivated by the powergrid toggle', function () {
    $warehouse = Warehouse::create([
        'name' => 'Almacén secundario',
        'is_active' => true,
    ]);

    Livewire::test(WarehouseTable::class)
        ->dispatch(
            'pg:toggleable-warehouseTable',
            'is_active_toggle',
            (string) $warehouse->id,
            '0',
        )
        ->assertDispatched('toast-show')
        ->assertSee('&quot;toggle&quot;:0', escape: false);

    expect($warehouse->refresh()->is_active)->toBeFalse();
});

test('unrelated plugin events do not update warehouses', function () {
    $warehouse = Warehouse::create([
        'name' => 'Almacén protegido',
        'is_active' => true,
    ]);

    Livewire::test(WarehouseTable::class)
        ->dispatch(
            'pg:toggleable-warehouseTable',
            'name',
            (string) $warehouse->id,
            '0',
        );

    expect($warehouse->refresh()->is_active)->toBeTrue();
});
