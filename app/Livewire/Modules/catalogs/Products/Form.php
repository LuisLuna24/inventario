<?php

namespace App\Livewire\Modules\catalogs\Products;

use App\Models\Category;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class Form extends Component
{
    private const IVA_RATE = 0.16;

    public ?Product $product = null;

    public int|string|null $categoryId = null;

    public int|string|null $supplierId = null;

    public string $name = '';

    public string $description = '';

    public string $sku = '';

    public string $barcode = '';

    public string $price = '';

    public string $cost = '';

    public string $costIva = '';

    public bool $applyIva = true;

    public string $costPackage = '';

    public string $unitsPackage = '';

    public string $minStock = '1';

    public int|string $porcent = 0;

    public bool $isLayawayAllowed = false;

    public bool $isReturnable = true;

    public bool $isActive = true;

    public function mount(?Product $product = null): void
    {
        $this->product = $product;

        if ($product === null) {
            $this->recalculateCommercialValues();

            return;
        }

        $this->categoryId = $product->category_id;
        $this->supplierId = $product->supplier_id;
        $this->name = $product->name;
        $this->description = $product->description ?? '';
        $this->sku = $product->sku ?? '';
        $this->barcode = $product->barcode ?? '';
        $this->price = (string) $product->price;
        $this->cost = (string) $product->cost;
        $this->costIva = (string) ($product->cost_iva ?? $product->cost);
        $this->applyIva = $product->apply_iva;
        $this->costPackage = (string) ($product->cost_package ?? '');
        $this->unitsPackage = (string) ($product->units_package ?? '');
        $this->minStock = (string) $product->min_stock;
        $this->porcent = $product->porcent;
        $this->isLayawayAllowed = $product->is_layaway_allowed;
        $this->isReturnable = $product->is_returnable;
        $this->isActive = $product->is_active;
        $this->recalculateUnitCost();
    }

    public function updatedCategoryId(int|string|null $categoryId): void
    {
        if ($categoryId === null || $categoryId === '') {
            return;
        }

        $category = Category::find($categoryId);

        if ($category !== null) {
            $this->porcent = $category->porcent;
            $this->recalculateCommercialValues();
        }
    }

    public function updatedCost(): void
    {
        $this->recalculateCommercialValues();
    }

    public function updatedCostPackage(): void
    {
        $this->recalculateUnitCost();
    }

    public function updatedUnitsPackage(): void
    {
        $this->recalculateUnitCost();
    }

    public function updatedApplyIva(): void
    {
        $this->recalculateCommercialValues();
    }

    public function updatedPorcent(): void
    {
        $this->recalculateCommercialValues();
    }

    public function submit(): void
    {
        $this->recalculateUnitCost();
        $this->recalculateCommercialValues();

        $validated = $this->validate([
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'supplierId' => ['nullable', 'integer', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($this->product?->id)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($this->product?->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'costIva' => ['required', 'numeric', 'min:0'],
            'applyIva' => ['boolean'],
            'costPackage' => ['nullable', 'numeric', 'min:0', 'required_with:unitsPackage'],
            'unitsPackage' => ['nullable', 'numeric', 'gt:0', 'required_with:costPackage'],
            'minStock' => ['required', 'numeric', 'min:0'],
            'porcent' => ['required', 'integer', 'min:0', 'lt:100'],
            'isLayawayAllowed' => ['boolean'],
            'isReturnable' => ['boolean'],
            'isActive' => ['boolean'],
        ], [
            'porcent.lt' => 'El margen debe ser menor a 100%.',
            'costPackage.required_with' => 'Indica el costo del paquete.',
            'unitsPackage.required_with' => 'Indica cuántas unidades contiene el paquete.',
            'unitsPackage.gt' => 'Las unidades por paquete deben ser mayores que cero.',
        ]);

        $wasEditing = $this->product !== null;

        try {
            DB::transaction(function () use ($validated): void {
                $this->product = Product::updateOrCreate(
                    ['id' => $this->product?->id],
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
                        'cost_iva' => $validated['costIva'],
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
                heading: 'Producto '.($wasEditing ? 'editado' : 'creado'),
                text: 'El producto se ha guardado correctamente.',
                variant: 'success',
            );

            $this->redirectRoute('products.index', navigate: true);
        } catch (Throwable) {
            Flux::toast(
                heading: 'Ocurrió un error',
                text: 'No se pudo guardar el producto. Inténtalo nuevamente.',
                variant: 'warning',
            );
        }
    }

    public function render(): View
    {
        return view('livewire.modules.catalogs.products.form');
    }

    private function recalculateUnitCost(): void
    {
        if (! is_numeric($this->costPackage) || ! is_numeric($this->unitsPackage)) {
            $this->recalculateCommercialValues();

            return;
        }

        $units = (float) $this->unitsPackage;

        if ($units <= 0) {
            $this->recalculateCommercialValues();

            return;
        }

        $this->cost = number_format((float) $this->costPackage / $units, 2, '.', '');
        $this->recalculateCommercialValues();
    }

    private function recalculateCommercialValues(): void
    {
        $cost = is_numeric($this->cost) ? max((float) $this->cost, 0) : 0;
        $margin = is_numeric($this->porcent) ? (float) $this->porcent : 0;
        $costWithIva = $this->applyIva ? $cost * (1 + self::IVA_RATE) : $cost;

        $this->costIva = number_format($costWithIva, 2, '.', '');
        $this->price = $margin >= 0 && $margin < 100
            ? number_format($costWithIva / (1 - ($margin / 100)), 2, '.', '')
            : '0.00';
    }
}
