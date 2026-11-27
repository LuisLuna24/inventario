<?php

namespace App\Livewire\Modules\Sales;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class Form extends Component
{
    use WithPagination;

    public int|string|null $customerId = null;
    public int|string|null $quoteId = null;
    public string $observation = '';
    public string $productSearch = '';
    public int|string|null $categoryId = null;
    public array $lines = [];
    public array $payments = [['payment_method_id' => '', 'amount' => '', 'reference' => '']];
    public string $subtotal = '0.00';
    public string $taxTotal = '0.00';
    public string $total = '0.00';

    public function mount(?Quote $quote = null): void
    {
        abort_unless(Auth::user()?->warehouse_id, 403, 'Tu usuario no tiene un almacén asignado.');
        if ($quote === null) return;
        abort_if($quote->status !== Quote::STATUS_PENDING || $quote->isExpired(), 409, 'El presupuesto no está vigente.');
        $quote->load('items.product');
        $this->quoteId = $quote->id;
        $this->customerId = $quote->customer_id;
        $this->lines = $quote->items->map(fn ($item): array => ['product_id' => $item->product_id, 'name' => $item->product->name, 'quantity' => (string) $item->quantity, 'unit_price' => (string) $item->unit_price, 'apply_iva' => (bool) $item->product->apply_iva])->values()->all();
        $this->recalculate();
    }

    public function selectProduct(int $id): void
    {
        foreach ($this->lines as $i => $line) if ((int) $line['product_id'] === $id) { $this->lines[$i]['quantity'] = (string) ((float) $line['quantity'] + 1); $this->recalculate(); return; }
        $product = Product::query()->where('is_active', true)->findOrFail($id);
        $this->lines[] = ['product_id' => $product->id, 'name' => $product->name, 'quantity' => '1', 'unit_price' => (string) $product->price, 'apply_iva' => (bool) $product->apply_iva];
        $this->recalculate();
    }

    public function updatedLines(): void { $this->recalculate(); }
    public function updatedQuoteId(int|string|null $value): void
    {
        if ($value === null || $value === '') return;
        $quote = Quote::query()->with('items.product')->where('warehouse_id', Auth::user()->warehouse_id)->where('status', Quote::STATUS_PENDING)->findOrFail($value);
        abort_if($quote->isExpired(), 409, 'El presupuesto ya venció.');
        $this->customerId = $quote->customer_id;
        $this->lines = $quote->items->map(fn ($item): array => ['product_id' => $item->product_id, 'name' => $item->product->name, 'quantity' => (string) $item->quantity, 'unit_price' => (string) $item->unit_price, 'apply_iva' => (bool) $item->product->apply_iva])->values()->all();
        $this->recalculate();
    }
    public function updatedProductSearch(): void { $this->resetPage('productsPage'); }
    public function updatedCategoryId(): void { $this->resetPage('productsPage'); }
    public function updatedPayments(): void { $this->syncPaymentAmounts(); }

    public function removeLine(int $index): void { unset($this->lines[$index]); $this->lines = array_values($this->lines); $this->recalculate(); }
    public function addPaymentMethod(): void { if (count($this->payments) < 2) { $this->payments[] = ['payment_method_id' => '', 'amount' => '', 'reference' => '']; $this->syncPaymentAmounts(); } }
    public function removePaymentMethod(int $index): void { if (count($this->payments) > 1) { unset($this->payments[$index]); $this->payments = array_values($this->payments); $this->syncPaymentAmounts(); } }

    public function submit(): void
    {
        $data = $this->validate([
            'customerId' => ['nullable', 'integer', 'exists:customers,id'], 'quoteId' => ['nullable', 'integer', 'exists:quotes,id'],
            'observation' => ['nullable', 'string', 'max:2000'], 'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'], 'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'payments' => ['required', 'array', 'min:1', 'max:2'], 'payments.*.payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'], 'payments.*.reference' => ['nullable', 'string', 'max:100'],
        ], ['lines.required' => 'Agrega al menos un producto.', 'lines.min' => 'Agrega al menos un producto.', 'payments.*.payment_method_id.required' => 'Selecciona un método de pago.']);
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        DB::transaction(function () use ($data, $user): void {
            $quote = null;
            if (! empty($data['quoteId'])) {
                $quote = Quote::query()->lockForUpdate()->with('items')->findOrFail($data['quoteId']);
                if ($quote->status !== Quote::STATUS_PENDING || $quote->isExpired() || (int) $quote->warehouse_id !== (int) $user->warehouse_id) throw ValidationException::withMessages(['quoteId' => 'El presupuesto no está vigente o no pertenece a tu almacén.']);
                if ($quote->sales()->exists()) throw ValidationException::withMessages(['quoteId' => 'El presupuesto ya fue convertido en venta.']);
            }
            $session = CashSession::query()->where('status', 'open')->whereHas('cashRegister', fn ($q) => $q->where('warehouse_id', $user->warehouse_id))->lockForUpdate()->first();
            if (! $session) throw ValidationException::withMessages(['payments' => 'No hay una sesión de caja abierta para el almacén de tu usuario.']);
            $products = Product::query()->whereIn('id', collect($data['lines'])->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
            $subtotal = $tax = $total = 0.0;
            $prepared = [];
            foreach ($data['lines'] as $line) {
                $product = $products->get((int) $line['product_id']);
                if (! $product || ! $product->is_active) throw ValidationException::withMessages(['lines' => 'Uno de los productos ya no está disponible.']);
                $quantity = round((float) $line['quantity'], 3);
                $unitPrice = (float) $product->price;
                $lineTotal = round($quantity * $unitPrice, 2);
                $lineTax = $product->apply_iva ? round($lineTotal * 16 / 116, 2) : 0;
                $available = StockBalance::query()->where('warehouse_id', $user->warehouse_id)->where('product_id', $product->id)->lockForUpdate()->first();
                $stock = $available ? (float) $available->physical_quantity - (float) $available->reserved_quantity : 0;
                if ($stock < $quantity) throw ValidationException::withMessages(['lines' => 'Existencias insuficientes para '.$product->name.'. Disponible: '.number_format(max($stock, 0), 3).'.']);
                $total += $lineTotal; $tax += $lineTax; $subtotal += $lineTotal - $lineTax;
                $prepared[] = ['product' => $product, 'quantity' => $quantity, 'unit_price' => $unitPrice, 'line_total' => $lineTotal, 'line_tax' => $lineTax];
            }
            $paymentTotal = round((float) collect($data['payments'])->sum('amount'), 2);
            if ($paymentTotal !== round($total, 2)) throw ValidationException::withMessages(['payments' => 'La suma de los pagos debe ser igual al total de la venta.']);
            $methods = PaymentMethod::query()->whereIn('id', collect($data['payments'])->pluck('payment_method_id'))->where('is_active', true)->count();
            if ($methods !== count($data['payments'])) throw ValidationException::withMessages(['payments' => 'Selecciona métodos de pago activos y distintos.']);
            if (collect($data['payments'])->pluck('payment_method_id')->unique()->count() !== count($data['payments'])) throw ValidationException::withMessages(['payments' => 'Usa métodos de pago diferentes.']);

            $sale = Sale::create(['quote_id' => $quote?->id, 'customer_id' => $data['customerId'] ?: $quote?->customer_id, 'warehouse_id' => $user->warehouse_id,
                'cash_session_id' => $session->id, 'created_by' => $user->id, 'voucher_type' => 'sale', 'serie' => 'VEN'.now()->format('Y'),
                'correlative' => Str::upper(Str::random(16)), 'date' => now(), 'subtotal' => round($subtotal, 2), 'discount_total' => 0,
                'tax_total' => round($tax, 2), 'total' => round($total, 2), 'status' => Sale::STATUS_COMPLETED, 'observation' => $data['observation'] ?: null]);
            foreach ($prepared as $line) {
                $product = $line['product'];
                $balance = StockBalance::query()->where('warehouse_id', $user->warehouse_id)->where('product_id', $product->id)->lockForUpdate()->firstOrFail();
                $quantityBalance = round((float) $balance->physical_quantity - $line['quantity'], 3);
                $balance->update(['physical_quantity' => $quantityBalance]);
                $item = $sale->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => $line['quantity'], 'unit_price' => $line['unit_price'], 'unit_cost' => $product->cost, 'discount_total' => 0, 'tax_total' => $line['line_tax'], 'line_total' => $line['line_total'], 'cost_total' => round($line['quantity'] * (float) $product->cost, 2)]);
                Inventory::create(['warehouse_id' => $user->warehouse_id, 'product_id' => $product->id, 'sale_item_id' => $item->id, 'movement_type' => 'sale', 'detail' => 'Venta '.$sale->serie.'-'.$sale->correlative, 'quantity_in' => 0, 'quantity_out' => $line['quantity'], 'cost_in' => 0, 'cost_out' => $product->cost, 'total_in' => 0, 'total_out' => $item->cost_total, 'quantity_balance' => $quantityBalance, 'cost_balance' => $product->cost, 'total_balance' => round($quantityBalance * (float) $product->cost, 2), 'created_by' => $user->id, 'occurred_at' => now()]);
            }
            foreach ($data['payments'] as $paymentData) {
                $payment = $sale->payments()->create(['cash_session_id' => $session->id, 'payment_method_id' => $paymentData['payment_method_id'], 'payment_kind' => 'new_payment', 'amount' => $paymentData['amount'], 'received_at' => now(), 'received_by' => $user->id, 'reference' => $paymentData['reference'] ?: null]);
                CashMovement::create(['cash_session_id' => $session->id, 'sale_payment_id' => $payment->id, 'direction' => 'in', 'movement_type' => 'sale_payment', 'amount' => $paymentData['amount'], 'created_by' => $user->id, 'occurred_at' => now(), 'description' => 'Pago de venta '.$sale->serie.'-'.$sale->correlative]);
            }
            if ($quote) $quote->update(['status' => Quote::STATUS_CONVERTED]);
            $sale->update(['correlative' => (string) $sale->id]);
        }, 3);

        Flux::toast(heading: 'Venta registrada', text: 'Se actualizó el inventario y se registró el pago.', variant: 'success');
        $this->redirectRoute('sales.index', navigate: true);
    }

    public function render(): View
    {
        $user = Auth::user(); $search = trim($this->productSearch);
        $products = Product::query()->where('is_active', true)->withSum(['stockBalances as physical_stock' => fn ($q) => $q->where('warehouse_id', $user->warehouse_id)], 'physical_quantity')->withSum(['stockBalances as reserved_stock' => fn ($q) => $q->where('warehouse_id', $user->warehouse_id)], 'reserved_quantity')
            ->when($search !== '', fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', "%$search%")->orWhere('sku', 'like', "%$search%")->orWhere('barcode', 'like', "%$search%")))
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))->orderBy('name')->paginate(16, ['*'], 'productsPage');
        $cashSessions = CashSession::query()->with('cashRegister')->where('status', 'open')->whereHas('cashRegister', fn ($q) => $q->where('warehouse_id', $user->warehouse_id))->get();
        return view('livewire.modules.sales.form', ['products' => $products, 'categories' => Category::query()->orderBy('name')->get(), 'methods' => PaymentMethod::query()->where('is_active', true)->orderBy('name')->get(), 'quotes' => Quote::query()->where('warehouse_id', $user->warehouse_id)->where('status', Quote::STATUS_PENDING)->where('expires_at', '>', now())->orderByDesc('date')->get(), 'customers' => \App\Models\Customer::query()->orderBy('name')->get(), 'hasOpenSession' => $cashSessions->isNotEmpty()]);
    }

    private function recalculate(): void
    {
        $subtotal = $tax = $total = 0.0;
        foreach ($this->lines as $line) { $lineTotal = round(max((float) ($line['quantity'] ?? 0), 0) * max((float) ($line['unit_price'] ?? 0), 0), 2); $lineTax = ! empty($line['apply_iva']) ? round($lineTotal * 16 / 116, 2) : 0; $total += $lineTotal; $tax += $lineTax; $subtotal += $lineTotal - $lineTax; }
        $this->total = number_format($total, 2, '.', ''); $this->taxTotal = number_format($tax, 2, '.', ''); $this->subtotal = number_format($subtotal, 2, '.', '');
        $this->syncPaymentAmounts();
    }

    private function syncPaymentAmounts(): void
    {
        if (count($this->payments) === 1) $this->payments[0]['amount'] = $this->total;
        elseif (count($this->payments) === 2) { $first = is_numeric($this->payments[0]['amount'] ?? null) ? (float) $this->payments[0]['amount'] : 0; $this->payments[1]['amount'] = number_format(max((float) $this->total - $first, 0), 2, '.', ''); }
    }
}
