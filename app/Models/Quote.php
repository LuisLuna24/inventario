<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'warehouse_id', 'voucher_type', 'serie', 'correlative', 'date', 'expires_at', 'total', 'observation', 'status'])]
class Quote extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'expires_at' => 'datetime',
            'total' => 'decimal:2',
        ];
    }

    public function getFolioAttribute(): string
    {
        return $this->serie.'-'.$this->correlative;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast() && $this->status === self::STATUS_PENDING;
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
        return $this->hasMany(QuoteItem::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
