<?php

namespace App\Livewire\Modules\Purchases;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\User;
use App\Services\Purchases\PurchaseTotals;
use DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class Form extends Component
{
    use WithPagination;

    public ?Purchase $purchase = null;

    public int|string|null $supplierId = null;

    public int|string|null $warehouseId = null;

    public int|string|null $purchaseOrderId = null;

    public string $date = '';

    public string $observation = '';

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    public int|string|null $productToAdd = null;

    public string $subtotal = '0.00';

    public string $taxTotal = '0.00';

    public string $total = '0.00';

    public int|string|null $cashSessionId = null;

    public string $paymentAmount = '';

    public string $paymentReference = '';

    public string $productSearch = '';

    public int|string|null $categoryId = null;

    public bool $showPaymentModal = false;

    public function mount(?Purchase $purchase = null): void
    {
        $this->purchase = $purchase;
        $this->date = now()->format('Y-m-d');

        if ($purchase === null) {
            $this->warehouseId = Auth::user()?->warehouse_id;

            return;
        }

        abort_unless($purchase->isDraft(), 409, 'La compra confirmada no puede editarse.');
        $purchase->load(['items.product', 'payments']);
        $this->supplierId = $purchase->supplier_id;
        $this->warehouseId = $purchase->warehouse_id;
        $this->purchaseOrderId = $purchase->purchase_order_id;
        $this->date = $purchase->date->format('Y-m-d');
        $this->observation = $purchase->observation ?? '';
        $this->lines = $purchase->items->map(fn (PurchaseItem $item): array => [
            'product_id' => $item->product_id,
            'name' => $item->product->name,
            'unit_type' => $item->unit_type,
            'quantity' => (string) $item->quantity,
            'unit_cost' => (string) $item->unit_cost,
            'piece_cost' => (string) $item->product->cost,
            'package_cost' => (string) ($item->product->cost_package ?? ''),
            'units_per_package' => (string) ($item->product->units_package ?? ''),
        ])->values()->all();

        $payment = $purchase->payments->first();

        if ($payment !== null) {
            $this->cashSessionId = $payment->cash_session_id;
            $this->paymentAmount = (string) $payment->amount;
            $this->paymentReference = $payment->reference ?? '';
        }

        $this->recalculatePreview();
    }

    public function updatedPurchaseOrderId(int|string|null $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $order = PurchaseOrder::query()->with(['items.product'])->find($value);

        if ($order === null) {
            return;
        }

        $this->supplierId = $order->supplier_id;
        $this->warehouseId = $order->warehouse_id;
        $this->lines = $order->items->map(fn ($item): array => [
            'product_id' => $item->product_id,
            'name' => $item->product->name,
            'unit_type' => PurchaseItem::UNIT_PIECE,
            'quantity' => (string) $item->quantity,
            'unit_cost' => (string) $item->unit_cost,
            'piece_cost' => (string) $item->product->cost,
            'package_cost' => (string) ($item->product->cost_package ?? ''),
            'units_per_package' => (string) ($item->product->units_package ?? ''),
        ])->values()->all();
        $this->recalculatePreview();
    }

    public function updatedSupplierId(): void
    {
        if ($this->purchaseOrderId === null || $this->purchaseOrderId === '') {
            return;
        }

        $belongsToSupplier = PurchaseOrder::query()
            ->whereKey($this->purchaseOrderId)
            ->where('supplier_id', $this->supplierId)
            ->exists();

        if (! $belongsToSupplier) {
            $this->purchaseOrderId = null;
            $this->lines = [];
            $this->recalculatePreview();
        }
    }

    public function updatedLines(mixed $value = null, ?string $key = null): void
    {
        if ($key !== null && str_ends_with($key, '.unit_type')) {
            $index = (int) explode('.', $key)[0];

            if (isset($this->lines[$index])) {
                $line = $this->lines[$index];

                if ($value === PurchaseItem::UNIT_PACKAGE) {
                    $packageCost = $line['package_cost'] ?? '';
                    $units = (float) ($line['units_per_package'] ?? 0);
                    $pieceCost = (float) ($line['piece_cost'] ?? 0);
                    $this->lines[$index]['unit_cost'] = $packageCost !== ''
                        ? (string) $packageCost
                        : number_format($pieceCost * $units, 4, '.', '');
                } else {
                    $this->lines[$index]['unit_cost'] = (string) ($line['piece_cost'] ?? '0');
                }
            }
        }

        $this->recalculatePreview();
    }

    public function addProduct(): void
    {
        $this->validateOnly('productToAdd', [
            'productToAdd' => ['required', 'integer', 'exists:products,id'],
        ], [
            'productToAdd.required' => 'Selecciona un producto para agregarlo.',
        ]);

        $product = Product::query()->findOrFail($this->productToAdd);

        if (collect($this->lines)->contains(fn (array $line): bool => (int) $line['product_id'] === $product->id)) {
            $this->addError('productToAdd', 'El producto ya está agregado a la compra.');

            return;
        }

        $this->lines[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_type' => PurchaseItem::UNIT_PIECE,
            'quantity' => '1',
            'unit_cost' => (string) $product->cost,
            'piece_cost' => (string) $product->cost,
            'package_cost' => (string) ($product->cost_package ?? ''),
            'units_per_package' => (string) ($product->units_package ?? ''),
        ];
        $this->productToAdd = null;
        $this->resetErrorBag('productToAdd');
        $this->recalculatePreview();
    }

    public function updatedProductSearch(): void
    {
        $this->resetPage('productsPage');
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage('productsPage');
    }

    public function selectProduct(int $productId): void
    {
        foreach ($this->lines as $index => $line) {
            if ((int) $line['product_id'] === $productId) {
                $current = is_numeric($line['quantity'] ?? null) ? (float) $line['quantity'] : 0;
                $this->lines[$index]['quantity'] = (string) ($current + 1);
                $this->recalculatePreview();

                return;
            }
        }

        $product = Product::query()->findOrFail($productId);

        $this->lines[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_type' => PurchaseItem::UNIT_PIECE,
            'quantity' => '1',
            'unit_cost' => (string) $product->cost,
            'piece_cost' => (string) $product->cost,
            'package_cost' => (string) ($product->cost_package ?? ''),
            'units_per_package' => (string) ($product->units_package ?? ''),
        ];
        $this->resetErrorBag('lines');
        $this->recalculatePreview();
    }

    public function incrementLine(int $index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $current = is_numeric($this->lines[$index]['quantity'] ?? null) ? (float) $this->lines[$index]['quantity'] : 0;
        $this->lines[$index]['quantity'] = (string) ($current + 1);
        $this->recalculatePreview();
    }

    public function decrementLine(int $index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $current = is_numeric($this->lines[$index]['quantity'] ?? null) ? (float) $this->lines[$index]['quantity'] : 0;
        $next = $current - 1;

        if ($next <= 0) {
            $this->removeLine($index);

            return;
        }

        $this->lines[$index]['quantity'] = (string) $next;
        $this->recalculatePreview();
    }

    public function clearPayment(): void
    {
        $this->cashSessionId = null;
        $this->paymentAmount = '';
        $this->paymentReference = '';
        $this->resetErrorBag(['cashSessionId', 'paymentAmount', 'paymentReference']);
        $this->showPaymentModal = false;
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        $this->recalculatePreview();
    }

    public function submit(PurchaseTotals $calculator): void
    {
        $validated = $this->validate([
            'supplierId' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouseId' => ['required', 'integer', 'exists:warehouses,id'],
            'purchaseOrderId' => [
                'nullable',
                'integer',
                Rule::exists('purchase_orders', 'id')->where('supplier_id', $this->supplierId),
                Rule::unique('purchases', 'purchase_order_id')->ignore($this->purchase?->id),
            ],
            'date' => ['required', 'date'],
            'observation' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'lines.*.unit_type' => ['required', Rule::in([PurchaseItem::UNIT_PIECE, PurchaseItem::UNIT_PACKAGE])],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'cashSessionId' => ['nullable', 'integer', 'exists:cash_sessions,id', 'required_with:paymentAmount'],
            'paymentAmount' => ['nullable', 'numeric', 'gt:0'],
            'paymentReference' => ['nullable', 'string', 'max:100'],
        ], [
            'supplierId.required' => 'Selecciona un proveedor.',
            'warehouseId.required' => 'Selecciona el almacén que recibirá la compra.',
            'purchaseOrderId.unique' => 'La orden seleccionada ya tiene una compra relacionada.',
            'lines.required' => 'Agrega al menos un producto.',
            'lines.min' => 'Agrega al menos un producto.',
            'lines.*.product_id.distinct' => 'No se puede repetir un producto.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',
            'lines.*.unit_cost.min' => 'El costo no puede ser negativo.',
            'cashSessionId.required_with' => 'Selecciona una sesión de caja para registrar el pago.',
            'paymentAmount.gt' => 'El pago debe ser mayor que cero.',
        ]);

        try {
            $totals = $calculator->calculate($validated['lines']);

            if ($validated['paymentAmount'] !== null && $validated['paymentAmount'] !== ''
                && (float) $validated['paymentAmount'] > $totals['total']) {
                throw ValidationException::withMessages([
                    'paymentAmount' => 'El pago no puede ser mayor al total de la compra.',
                ]);
            }

            $user = Auth::user();

            if (! $user instanceof User) {
                abort(403);
            }

            $this->purchase = DB::transaction(function () use ($validated, $totals, $user): Purchase {
                $purchase = $this->purchase === null
                    ? $this->createPurchase($validated, $totals)
                    : Purchase::query()->lockForUpdate()->findOrFail($this->purchase->id);

                if (! $purchase->isDraft()) {
                    throw new DomainException('La compra confirmada no puede modificarse.');
                }

                $purchase->update([
                    'purchase_order_id' => $validated['purchaseOrderId'] ?: null,
                    'supplier_id' => $validated['supplierId'],
                    'warehouse_id' => $validated['warehouseId'],
                    'date' => $validated['date'],
                    'subtotal' => $totals['subtotal'],
                    'tax_total' => $totals['tax_total'],
                    'total' => $totals['total'],
                    'observation' => $validated['observation'] ?: null,
                ]);

                $purchase->items()->delete();
                $purchase->items()->createMany($totals['items']);
                $this->syncPayment($purchase, $validated, $user);

                return $purchase->refresh();
            }, 3);

            Flux::toast(
                heading: 'Borrador guardado',
                text: 'La compra se guardó correctamente.',
                variant: 'success',
            );
            $this->redirectRoute('purchases.index', navigate: true);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            Flux::toast(
                heading: 'No se pudo guardar',
                text: $exception instanceof DomainException
                    ? $exception->getMessage()
                    : 'Ocurrió un error al guardar la compra.',
                variant: 'warning',
            );
        }
    }

    public function render(): View
    {
        $purchaseOrders = PurchaseOrder::query()
            ->where('status', '!=', PurchaseOrder::STATUS_PURCHASED)
            ->when($this->supplierId, fn ($query) => $query->where('supplier_id', $this->supplierId))
            ->where(function ($query): void {
                $query->whereDoesntHave('purchase')
                    ->when($this->purchaseOrderId, fn ($query) => $query->orWhereKey($this->purchaseOrderId));
            })
            ->orderByDesc('date')
            ->limit(50)
            ->get(['id', 'serie', 'correlative', 'supplier_id']);

        $cashSessions = CashSession::query()
            ->with('cashRegister:id,name,warehouse_id')
            ->where('status', 'open')
            ->when($this->warehouseId, fn ($query) => $query->whereHas(
                'cashRegister',
                fn ($query) => $query->where('warehouse_id', $this->warehouseId),
            ))
            ->get();

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $search = trim($this->productSearch);
        $catalogProducts = Product::query()
            ->where('is_active', true)
            ->withSum('stockBalances as total_stock', 'physical_quantity')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($this->categoryId !== null && $this->categoryId !== '', fn ($query) => $query->where('category_id', $this->categoryId))
            ->orderBy('name')
            ->paginate(16, ['*'], 'productsPage');

        return view('livewire.modules.purchases.form', compact(
            'purchaseOrders',
            'cashSessions',
            'categories',
            'catalogProducts',
        ));
    }

    /** @param array<string, mixed> $validated @param array<string, mixed> $totals */
    private function createPurchase(array $validated, array $totals): Purchase
    {
        $purchase = Purchase::create([
            'purchase_order_id' => $validated['purchaseOrderId'] ?: null,
            'supplier_id' => $validated['supplierId'],
            'warehouse_id' => $validated['warehouseId'],
            'voucher_type' => 'purchase',
            'serie' => 'COMP',
            'correlative' => Str::upper(Str::random(16)),
            'date' => $validated['date'],
            'subtotal' => $totals['subtotal'],
            'tax_total' => $totals['tax_total'],
            'total' => $totals['total'],
            'status' => Purchase::STATUS_DRAFT,
            'observation' => $validated['observation'] ?: null,
        ]);

        $purchase->update(['correlative' => str_pad((string) $purchase->id, 6, '0', STR_PAD_LEFT)]);

        return $purchase;
    }

    /** @param array<string, mixed> $validated */
    private function syncPayment(Purchase $purchase, array $validated, User $user): void
    {
        $payment = $purchase->payments()->first();
        $amount = $validated['paymentAmount'];

        if ($amount === null || $amount === '') {
            $payment?->cashMovement()?->delete();
            $payment?->delete();

            return;
        }

        $session = CashSession::query()
            ->whereKey($validated['cashSessionId'])
            ->where('status', 'open')
            ->whereHas('cashRegister', fn ($query) => $query->where('warehouse_id', $purchase->warehouse_id))
            ->first();

        if ($session === null) {
            throw ValidationException::withMessages([
                'cashSessionId' => 'La sesión de caja debe estar abierta y pertenecer al almacén seleccionado.',
            ]);
        }

        $payment = PurchasePayment::updateOrCreate(
            ['id' => $payment?->id],
            [
                'purchase_id' => $purchase->id,
                'cash_session_id' => $session->id,
                'amount' => $amount,
                'paid_at' => now(),
                'paid_by' => $user->id,
                'reference' => $validated['paymentReference'] ?: null,
            ],
        );

        CashMovement::updateOrCreate(
            ['purchase_payment_id' => $payment->id],
            [
                'cash_session_id' => $session->id,
                'direction' => 'out',
                'movement_type' => 'purchase_payment',
                'amount' => $amount,
                'created_by' => $user->id,
                'occurred_at' => now(),
                'description' => 'Pago de compra '.$purchase->folio,
            ],
        );
    }

    private function recalculatePreview(): void
    {
        $products = Product::query()
            ->whereKey(collect($this->lines)->pluck('product_id')->filter())
            ->get()
            ->keyBy('id');
        $subtotal = 0.0;
        $tax = 0.0;

        foreach ($this->lines as $line) {
            $quantity = is_numeric($line['quantity'] ?? null) ? max((float) $line['quantity'], 0) : 0;
            $cost = is_numeric($line['unit_cost'] ?? null) ? max((float) $line['unit_cost'], 0) : 0;
            $lineTotal = round($quantity * $cost, 2);
            $subtotal += $lineTotal;

            if ($products->get((int) ($line['product_id'] ?? 0))?->apply_iva) {
                $tax += round($lineTotal * PurchaseTotals::TAX_RATE, 2);
            }
        }

        $this->subtotal = number_format($subtotal, 2, '.', '');
        $this->taxTotal = number_format($tax, 2, '.', '');
        $this->total = number_format($subtotal + $tax, 2, '.', '');
    }
}
