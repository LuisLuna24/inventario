<?php

namespace App\Livewire\Modules\catalogs\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Index extends Component
{
    public ?Product $productBeingEdited = null;

    public int|string|null $categoryId = null;

    public int|string|null $supplierId = null;

    public string $name = '';

    public string $description = '';

    public string $sku = '';

    public string $barcode = '';

    public string $price = '0';

    public string $cost = '0';

    public bool $applyIva = true;

    public string $costPackage = '';

    public string $unitsPackage = '';

    public string $minStock = '1';

    public int|string $porcent = 0;

    public bool $isLayawayAllowed = false;

    public bool $isReturnable = true;

    public bool $isActive = true;

    public bool $modalForm = false;

    public function create(): void
    {
        $this->resetForm();
        $this->modalForm = true;
    }

    #[On('edit-product')]
    public function edit(int|string $rowId): void
    {
        $this->resetForm();
        $this->productBeingEdited = Product::findOrFail($rowId);
        $this->categoryId = $this->productBeingEdited->category_id;
        $this->supplierId = $this->productBeingEdited->supplier_id;
        $this->name = $this->productBeingEdited->name;
        $this->description = $this->productBeingEdited->description ?? '';
        $this->sku = $this->productBeingEdited->sku ?? '';
        $this->barcode = $this->productBeingEdited->barcode ?? '';
        $this->price = (string) $this->productBeingEdited->price;
        $this->cost = (string) $this->productBeingEdited->cost;
        $this->applyIva = $this->productBeingEdited->apply_iva;
        $this->costPackage = (string) ($this->productBeingEdited->cost_package ?? '');
        $this->unitsPackage = (string) ($this->productBeingEdited->units_package ?? '');
        $this->minStock = (string) $this->productBeingEdited->min_stock;
        $this->porcent = $this->productBeingEdited->porcent;
        $this->isLayawayAllowed = $this->productBeingEdited->is_layaway_allowed;
        $this->isReturnable = $this->productBeingEdited->is_returnable;
        $this->isActive = $this->productBeingEdited->is_active;
        $this->modalForm = true;
    }

    public function submit(): void
    {
        $productId = $this->productBeingEdited?->id;
        $validated = $this->validate([
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'supplierId' => ['nullable', 'integer', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($productId)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($productId)],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'applyIva' => ['boolean'],
            'costPackage' => ['nullable', 'numeric', 'min:0'],
            'unitsPackage' => ['nullable', 'numeric', 'gt:0'],
            'minStock' => ['required', 'numeric', 'min:0'],
            'porcent' => ['required', 'integer', 'min:0'],
            'isLayawayAllowed' => ['boolean'],
            'isReturnable' => ['boolean'],
            'isActive' => ['boolean'],
        ]);

        try {
            DB::transaction(function () use ($validated): void {
                Product::updateOrCreate(
                    ['id' => $this->productBeingEdited?->id],
                    [
                        'category_id' => $validated['categoryId'],
                        'supplier_id' => $validated['supplierId'] ?: null,
                        'name' => $validated['name'],
                        'description' => $validated['description'] ?: null,
                        'sku' => $validated['sku'] ?: null,
                        'barcode' => $validated['barcode'] ?: null,
                        'price' => $validated['price'],
                        'cost' => $validated['cost'],
                        'apply_iva' => $validated['applyIva'],
                        'cost_package' => $validated['costPackage'] === '' ? null : $validated['costPackage'],
                        'units_package' => $validated['unitsPackage'] === '' ? null : $validated['unitsPackage'],
                        'min_stock' => $validated['minStock'],
                        'porcent' => $validated['porcent'],
                        'is_layaway_allowed' => $validated['isLayawayAllowed'],
                        'is_returnable' => $validated['isReturnable'],
                        'is_active' => $validated['isActive'],
                    ],
                );
            });

            Flux::toast(
                heading: 'Producto '.($this->productBeingEdited ? 'editado' : 'creado'),
                text: 'El producto se ha guardado correctamente.',
                variant: 'success',
            );
            $this->closeModal();
            $this->dispatch('pg:eventRefresh-productTable');
        } catch (Throwable) {
            Flux::toast(
                heading: 'Ocurrió un error',
                text: 'No se pudo guardar el producto.',
                variant: 'warning',
            );
        }
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->modalForm = false;
    }

    #[On('delete-product')]
    public function delete(int|string $rowId): void
    {
        $product = Product::findOrFail($rowId);

        if ($this->hasDependencies($product)) {
            Flux::toast(
                heading: 'No se puede eliminar',
                text: 'El producto está relacionado con otros registros.',
                variant: 'warning',
            );

            return;
        }

        try {
            $product->delete();
        } catch (Throwable) {
            Flux::toast(
                heading: 'No se puede eliminar',
                text: 'El producto está relacionado con otros registros.',
                variant: 'warning',
            );

            return;
        }

        Flux::toast(
            heading: 'Producto eliminado',
            text: 'El producto se ha eliminado correctamente.',
            variant: 'success',
        );
        $this->dispatch('pg:eventRefresh-productTable');
    }

    public function render(): View
    {
        return view('livewire.modules.catalogs.products.index', [
            'categoryOptions' => Category::query()->orderBy('name')->get(['id', 'name']),
            'supplierOptions' => Supplier::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    private function hasDependencies(Product $product): bool
    {
        if ($product->images()->exists()) {
            return true;
        }

        $references = [
            'stock_balances',
            'product_tags',
            'quote_items',
            'layaway_items',
            'sale_items',
            'purchase_items',
            'purchase_order_items',
            'exchange_issued_items',
            'transfer_items',
            'inventories',
            'inventory_adjustment_items',
            'delivery_note_items',
        ];

        foreach ($references as $table) {
            if (DB::table($table)->where('product_id', $product->id)->exists()) {
                return true;
            }
        }

        return false;
    }
}
