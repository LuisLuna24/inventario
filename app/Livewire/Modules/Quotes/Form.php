<?php

namespace App\Livewire\Modules\Quotes;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Form extends Component
{
    use WithPagination;

    public ?Quote $quote = null;
    public int|string|null $customerId = null;
    public string $observation = '';
    public string $productSearch = '';
    public int|string|null $categoryId = null;
    public array $lines = [];
    public string $total = '0.00';

    public function mount(?Quote $quote = null): void
    {
        $this->quote = $quote;
        abort_unless(Auth::user()?->warehouse_id, 403, 'Tu usuario no tiene un almacén asignado.');
        if ($quote === null) return;

        abort_if($quote->status !== Quote::STATUS_PENDING || $quote->isExpired(), 409, 'Solo se pueden editar presupuestos pendientes y vigentes.');
        $quote->load('items.product');
        $this->customerId = $quote->customer_id;
        $this->observation = $quote->observation ?? '';
        $this->lines = $quote->items->map(fn (QuoteItem $item): array => [
            'product_id' => $item->product_id,
            'name' => $item->product->name,
            'quantity' => (string) $item->quantity,
            'unit_price' => (string) $item->unit_price,
            'apply_iva' => (bool) $item->product->apply_iva,
        ])->values()->all();
        $this->recalculate();
    }

    public function selectProduct(int $id): void
    {
        foreach ($this->lines as $i => $line) {
            if ((int) $line['product_id'] === $id) {
                $this->lines[$i]['quantity'] = (string) ((float) $line['quantity'] + 1);
                $this->recalculate();
                return;
            }
        }
        $product = Product::query()->where('is_active', true)->findOrFail($id);
        $this->lines[] = ['product_id' => $product->id, 'name' => $product->name, 'quantity' => '1', 'unit_price' => (string) $product->price, 'apply_iva' => (bool) $product->apply_iva];
        $this->recalculate();
    }

    public function updatedLines(): void { $this->recalculate(); }
    public function updatedProductSearch(): void { $this->resetPage('productsPage'); }
    public function updatedCategoryId(): void { $this->resetPage('productsPage'); }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        $this->recalculate();
    }

    public function submit(): void
    {
        $data = $this->validate([
            'customerId' => ['nullable', 'integer', 'exists:customers,id'],
            'observation' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ], ['lines.required' => 'Agrega al menos un producto.', 'lines.min' => 'Agrega al menos un producto.']);
        $warehouseId = Auth::user()->warehouse_id;
        $date = now();

        DB::transaction(function () use ($data, $warehouseId, $date): void {
            $existingPrices = $this->quote?->items()->pluck('unit_price', 'product_id') ?? collect();
            $quote = $this->quote ?? Quote::create([
                'warehouse_id' => $warehouseId, 'voucher_type' => 'quote', 'serie' => 'PRE'.now()->format('Y'),
                'correlative' => Str::upper(Str::random(16)), 'date' => $date, 'expires_at' => $date->copy()->addDays(7),
                'total' => $this->total, 'status' => Quote::STATUS_PENDING,
            ]);
            abort_unless($quote->warehouse_id === (int) $warehouseId && $quote->status === Quote::STATUS_PENDING, 409);
            $quote->update(['customer_id' => $data['customerId'] ?: null, 'date' => $date, 'expires_at' => $date->copy()->addDays(7), 'total' => $this->total, 'observation' => $data['observation'] ?: null]);
            $quote->items()->delete();
            $products = Product::query()->whereIn('id', collect($data['lines'])->pluck('product_id'))->get()->keyBy('id');
            $items = collect($data['lines'])->map(function (array $line) use ($products, $existingPrices): array {
                $unitPrice = (float) ($existingPrices->get($line['product_id']) ?? $products->get((int) $line['product_id'])?->price ?? 0);
                return ['product_id' => $line['product_id'], 'quantity' => $line['quantity'], 'unit_price' => $unitPrice,
                    'line_total' => round((float) $line['quantity'] * $unitPrice, 2), 'created_at' => now()];
            })->all();
            $quote->update(['total' => collect($items)->sum('line_total')]);
            $this->total = number_format((float) collect($items)->sum('line_total'), 2, '.', '');
            $quote->items()->createMany($items);
            if ($quote->wasRecentlyCreated) $quote->update(['correlative' => (string) $quote->id]);
            $this->quote = $quote;
        }, 3);

        Flux::toast(heading: 'Presupuesto guardado', text: 'Vence en 7 días.', variant: 'success');
        $this->redirectRoute('quotes.index', navigate: true);
    }

    public function render(): View
    {
        $search = trim($this->productSearch);
        $products = Product::query()->where('is_active', true)->withSum(['stockBalances as available_stock' => fn ($q) => $q->where('warehouse_id', Auth::user()->warehouse_id)], 'physical_quantity')->withSum(['stockBalances as reserved_stock' => fn ($q) => $q->where('warehouse_id', Auth::user()->warehouse_id)], 'reserved_quantity')
            ->when($search !== '', fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', "%$search%")->orWhere('sku', 'like', "%$search%")->orWhere('barcode', 'like', "%$search%")))
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))->orderBy('name')->paginate(16, ['*'], 'productsPage');
        return view('livewire.modules.quotes.form', ['products' => $products, 'categories' => Category::query()->orderBy('name')->get(), 'customers' => Customer::query()->orderBy('name')->get()]);
    }

    private function recalculate(): void
    {
        $total = 0;
        foreach ($this->lines as $line) $total += round(max((float) ($line['quantity'] ?? 0), 0) * max((float) ($line['unit_price'] ?? 0), 0), 2);
        $this->total = number_format($total, 2, '.', '');
    }
}
