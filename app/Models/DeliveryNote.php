<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'warehouse_id', 'guest_name', 'guest_phone', 'guest_email', 'voucher_type', 'serie', 'correlative', 'date', 'total', 'installment', 'observation', 'status'])]
class DeliveryNote extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'total' => 'decimal:2',
            'installment' => 'decimal:2',
            'status' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }
}
