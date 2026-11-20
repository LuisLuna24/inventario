<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sale_return_id', 'difference_amount', 'completed_at'])]
class SaleExchange extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'difference_amount' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function issuedItems(): HasMany
    {
        return $this->hasMany(ExchangeIssuedItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ExchangePayment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
