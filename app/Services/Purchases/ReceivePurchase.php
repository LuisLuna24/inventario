<?php

namespace App\Services\Purchases;

use App\Exceptions\ExcessPurchaseQuantityException;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReceivePurchase
{
    public function handle(Purchase $purchase, User $user, bool $excessConfirmed = false): Purchase
    {
        return DB::transaction(function () use ($purchase, $user, $excessConfirmed): Purchase {
            $lockedPurchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->getKey());

            if ($lockedPurchase->isReceived()) {
                return $lockedPurchase;
            }

            if (! $lockedPurchase->isDraft()) {
                throw new DomainException('Solo las compras en borrador pueden confirmarse.');
            }

            $lockedPurchase->load(['items.product', 'purchaseOrder.items.product']);

            if ($lockedPurchase->items->isEmpty()) {
                throw new DomainException('La compra debe contener al menos un producto.');
            }

            $excessProducts = $this->excessProducts($lockedPurchase);

            if ($excessProducts !== [] && ! $excessConfirmed) {
                throw new ExcessPurchaseQuantityException($excessProducts);
            }

            foreach ($lockedPurchase->items as $item) {
                $this->receiveItem($lockedPurchase, $item, $user);
            }

            $lockedPurchase->forceFill([
                'status' => Purchase::STATUS_RECEIVED,
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
            ])->save();

            if ($lockedPurchase->purchaseOrder !== null) {
                $lockedPurchase->purchaseOrder->update(['status' => PurchaseOrder::STATUS_PURCHASED]);
            }

            return $lockedPurchase->refresh();
        }, 3);
    }

    /** @return array<int, string> */
    private function excessProducts(Purchase $purchase): array
    {
        if ($purchase->purchaseOrder === null) {
            return [];
        }

        $ordered = $purchase->purchaseOrder->items
            ->groupBy('product_id')
            ->map(fn ($items): float => (float) $items->sum('quantity'));

        return $purchase->items
            ->groupBy('product_id')
            ->filter(function ($items, int|string $productId) use ($ordered): bool {
                $receivedPieces = $items->sum(fn (PurchaseItem $item): float => $item->inventoryQuantity());

                return $receivedPieces > (float) $ordered->get($productId, 0);
            })
            ->map(fn ($items): string => $items->first()->product->name)
            ->values()
            ->all();
    }

    private function receiveItem(Purchase $purchase, PurchaseItem $item, User $user): void
    {
        if (Inventory::query()->where('purchase_item_id', $item->id)->exists()) {
            throw new DomainException('La compra ya generó movimientos de inventario.');
        }

        DB::table('stock_balances')->upsert([
            [
                'warehouse_id' => $purchase->warehouse_id,
                'product_id' => $item->product_id,
                'physical_quantity' => 0,
                'reserved_quantity' => 0,
                'updated_at' => now(),
            ],
        ], ['warehouse_id', 'product_id'], []);

        $balance = StockBalance::query()
            ->where('warehouse_id', $purchase->warehouse_id)
            ->where('product_id', $item->product_id)
            ->lockForUpdate()
            ->firstOrFail();

        $quantityIn = round($item->inventoryQuantity(), 3);
        $unitCost = round($item->inventoryUnitCost(), 4);
        $newQuantity = round((float) $balance->physical_quantity + $quantityIn, 3);

        $balance->update(['physical_quantity' => $newQuantity]);

        Inventory::create([
            'warehouse_id' => $purchase->warehouse_id,
            'product_id' => $item->product_id,
            'purchase_item_id' => $item->id,
            'movement_type' => 'purchase',
            'detail' => 'Compra '.$purchase->folio,
            'quantity_in' => $quantityIn,
            'quantity_out' => 0,
            'cost_in' => $unitCost,
            'cost_out' => 0,
            'total_in' => round($quantityIn * $unitCost, 2),
            'total_out' => 0,
            'quantity_balance' => $newQuantity,
            'cost_balance' => $unitCost,
            'total_balance' => round($newQuantity * $unitCost, 2),
            'created_by' => $user->id,
            'occurred_at' => now(),
        ]);

        $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
        $product->cost = $unitCost;
        $product->cost_iva = round($unitCost * ($product->apply_iva ? 1.16 : 1), 4);

        if ($item->unit_type === PurchaseItem::UNIT_PACKAGE) {
            $product->cost_package = $item->unit_cost;
            $product->units_package = $item->units_per_package;
        }

        $product->save();
    }
}
