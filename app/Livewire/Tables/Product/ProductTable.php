<?php

namespace App\Livewire\Tables\Product;

use App\Models\Product;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class ProductTable extends PowerGridComponent
{
    public string $tableName = 'productTable';

    public function setUp(): array
    {
        return [
            'header' => PowerGrid::header()->showSearchInput(),
            'footer' => PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    /** @return Builder<Product> */
    public function datasource(): Builder
    {
        return Product::query()->with(['category', 'supplier']);
    }

    public function relationSearch(): array
    {
        return [
            'category' => ['name'],
            'supplier' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('sku')
            ->add('barcode')
            ->add('category_name', fn (Product $product) => $product->category->name)
            ->add('supplier_name', fn (Product $product) => $product->supplier_id === null ? '—' : $product->supplier->name)
            ->add('price_formatted', fn (Product $product) => '$'.number_format((float) $product->price, 2))
            ->add('min_stock')
            ->add('is_active_toggle', fn (Product $product) => (int) $product->is_active);
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),
            Column::make('Nombre', 'name')->sortable()->searchable(),
            Column::make('Categoría', 'category_name'),
            Column::make('Proveedor', 'supplier_name'),
            Column::make('SKU', 'sku')->searchable(),
            Column::make('Código de barras', 'barcode')->searchable(),
            Column::make('Precio', 'price_formatted', 'price')->sortable(),
            Column::make('Stock mínimo', 'min_stock')->sortable(),
            Column::make('Estatus', 'is_active_toggle')->toggleable(true, 'Activo', 'Inactivo'),
            Column::action('Acciones'),
        ];
    }

    public function edit(int|string $rowId): void
    {
        $this->dispatch('edit-product', rowId: $rowId);
    }

    public function delete(int|string $rowId): void
    {
        $this->dispatch('delete-product', rowId: $rowId);
    }

    /** @param array<string, mixed> $params */
    public function onPluginUpdated(string $plugin, string $event, array $params): void
    {
        if ($plugin !== 'toggleable' || $event !== 'updated' || data_get($params, 'field') !== 'is_active_toggle') {
            return;
        }

        $productId = data_get($params, 'id');
        $value = data_get($params, 'value');

        if (! is_scalar($productId) || ! is_scalar($value) || ! in_array((string) $value, ['0', '1'], true)) {
            return;
        }

        $product = Product::findOrFail((string) $productId);
        $product->is_active = (string) $value === '1';
        $product->save();

        Flux::toast(
            variant: 'success',
            text: $product->is_active
                ? 'Producto activado correctamente.'
                : 'Producto desactivado correctamente.',
        );
    }

    /** @return array<int, Button> */
    public function actions(Product $row): array
    {
        return [
            Button::add('actions')
                ->view('livewire.modules.catalogs.products.components.actions'),
        ];
    }
}
