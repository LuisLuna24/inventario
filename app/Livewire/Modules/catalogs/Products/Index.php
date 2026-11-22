<?php

namespace App\Livewire\Modules\catalogs\Products;

use App\Models\Product;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Index extends Component
{
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
        return view('livewire.modules.catalogs.products.index');
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
