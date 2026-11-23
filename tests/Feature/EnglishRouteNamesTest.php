<?php

use Illuminate\Support\Facades\Route;

test('application routes use english names and parameters', function () {
    $expectedRoutes = [
        'warehouses.index' => 'warehouses',
        'employees.edit' => 'employees/{employee}/edit',
        'roles.edit' => 'roles/{role}/edit',
        'permissions.edit' => 'permissions/{permission}/edit',
        'customers.edit' => 'customers/{customer}/edit',
        'suppliers.edit' => 'suppliers/{supplier}/edit',
        'identities.edit' => 'identities/{identity}/edit',
        'categories.index' => 'categories',
        'products.edit' => 'products/{product}/edit',
        'api.catalogs.categories' => 'api/catalogs/categories',
        'api.catalogs.suppliers' => 'api/catalogs/suppliers',
        'api.people.identities' => 'api/people/identities',
    ];

    foreach ($expectedRoutes as $name => $uri) {
        expect(Route::getRoutes()->getByName($name))
            ->not->toBeNull()
            ->and(Route::getRoutes()->getByName($name)->uri())
            ->toBe($uri);
    }

    foreach ([
        'almacenes.index',
        'empleados.index',
        'permisos.index',
        'clientes.index',
        'proveedores.index',
        'identidades.index',
        'categorias.index',
        'productos.index',
        'api.peoples.identities',
    ] as $oldRouteName) {
        expect(Route::has($oldRouteName))->toBeFalse();
    }
});
