<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['warehouse_id', 'product_id', 'sale_item_id', 'purchase_item_id', 'sale_return_item_id', 'exchange_issued_item_id', 'transfer_item_id', 'adjustment_item_id', 'movement_type', 'detail', 'quantity_in', 'quantity_out', 'cost_in', 'cost_out', 'total_in', 'total_out', 'quantity_balance', 'cost_balance', 'total_balance', 'created_by', 'occurred_at'])]
class Inventory extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantity_in' => 'decimal:3', 'quantity_out' => 'decimal:3',
            'cost_in' => 'decimal:4', 'cost_out' => 'decimal:4',
            'total_in' => 'decimal:2', 'total_out' => 'decimal:2',
            'quantity_balance' => 'decimal:3', 'cost_balance' => 'decimal:4',
            'total_balance' => 'decimal:2', 'occurred_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function saleReturnItem(): BelongsTo
    {
        return $this->belongsTo(SaleReturnItem::class);
    }

    public function exchangeIssuedItem(): BelongsTo
    {
        return $this->belongsTo(ExchangeIssuedItem::class);
    }

    public function transferItem(): BelongsTo
    {
        return $this->belongsTo(TransferItem::class);
    }

    public function adjustmentItem(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustmentItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
