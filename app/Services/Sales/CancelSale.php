<?php

namespace App\Services\Sales;

use App\Models\CashMovement;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CancelSale
{
    public function handle(Sale $sale, User $user): void
    {
        DB::transaction(function () use ($sale, $user): void {
            $sale = Sale::query()->lockForUpdate()->with(['items', 'payments.cashMovement'])->findOrFail($sale->id);
            if ($sale->status !== Sale::STATUS_COMPLETED) throw new DomainException('Solo se pueden cancelar ventas completadas.');
            if ($sale->returns()->exists()) throw new DomainException('No se puede cancelar una venta que ya tiene devoluciones registradas.');

            foreach ($sale->items as $item) {
                $balance = StockBalance::query()->where('warehouse_id', $sale->warehouse_id)->where('product_id', $item->product_id)->lockForUpdate()->firstOrFail();
                $oldQuantity = (float) $balance->physical_quantity;
                $newQuantity = round($oldQuantity + (float) $item->quantity, 3);
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $newAverageCost = $newQuantity > 0
                    ? round((($oldQuantity * (float) $product->cost) + (float) $item->cost_total) / $newQuantity, 4)
                    : (float) $item->unit_cost;
                $balance->update(['physical_quantity' => $newQuantity]);
                $product->cost = $newAverageCost;
                $product->cost_iva = round($newAverageCost * ($product->apply_iva ? 1.16 : 1), 4);
                $product->save();
                Inventory::create(['warehouse_id' => $sale->warehouse_id, 'product_id' => $item->product_id, 'movement_type' => 'sale_cancellation', 'detail' => 'Cancelación '.$sale->folio, 'quantity_in' => $item->quantity, 'quantity_out' => 0, 'cost_in' => $newAverageCost, 'cost_out' => 0, 'total_in' => $item->cost_total, 'total_out' => 0, 'quantity_balance' => $newQuantity, 'cost_balance' => $newAverageCost, 'total_balance' => round($newQuantity * $newAverageCost, 2), 'created_by' => $user->id, 'occurred_at' => now()]);
            }

            foreach ($sale->payments as $payment) {
                $original = $payment->cashMovement;
                if ($original === null || CashMovement::query()->where('reversal_of_id', $original->id)->exists()) continue;
                CashMovement::create(['cash_session_id' => $original->cash_session_id, 'reversal_of_id' => $original->id, 'direction' => 'out', 'movement_type' => 'sale_cancellation', 'amount' => $payment->amount, 'created_by' => $user->id, 'occurred_at' => now(), 'description' => 'Reverso de cobro '.$sale->folio]);
            }
            $sale->update(['status' => Sale::STATUS_CANCELLED]);
        }, 3);
    }
}
