<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['purchase_id', 'product_id', 'unit_type', 'units_per_package', 'quantity', 'unit_cost', 'line_total', 'tax_rate', 'tax_amount'])]
class PurchaseItem extends Model
{
    public const UNIT_PIECE = 'piece';

    public const UNIT_PACKAGE = 'package';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'units_per_package' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'line_total' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function inventoryQuantity(): float
    {
        return $this->unit_type === self::UNIT_PACKAGE
            ? (float) $this->quantity * (float) $this->units_per_package
            : (float) $this->quantity;
    }

    public function inventoryUnitCost(): float
    {
        return $this->unit_type === self::UNIT_PACKAGE
            ? (float) $this->unit_cost / (float) $this->units_per_package
            : (float) $this->unit_cost;
    }
}
