<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['inventory_adjustment_id', 'product_id', 'quantity_before', 'quantity_after', 'quantity_difference', 'unit_cost'])]
class InventoryAdjustmentItem extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantity_before' => 'decimal:3',
            'quantity_after' => 'decimal:3',
            'quantity_difference' => 'decimal:3',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function inventoryAdjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }
}
