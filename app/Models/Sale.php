<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['quote_id', 'customer_id', 'warehouse_id', 'cash_session_id', 'layaway_id', 'created_by', 'voucher_type', 'serie', 'correlative', 'date', 'subtotal', 'discount_total', 'tax_total', 'total', 'status', 'observation'])]
class Sale extends Model
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function layaway(): BelongsTo
    {
        return $this->belongsTo(Layaway::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function getFolioAttribute(): string
    {
        return $this->serie.'-'.$this->correlative;
    }
}
