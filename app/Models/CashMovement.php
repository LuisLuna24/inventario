<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['cash_session_id', 'sale_payment_id', 'exchange_payment_id', 'layaway_payment_id', 'expense_id', 'purchase_payment_id', 'refund_id', 'reversal_of_id', 'direction', 'movement_type', 'amount', 'created_by', 'authorized_by', 'occurred_at', 'description'])]
class CashMovement extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function salePayment(): BelongsTo
    {
        return $this->belongsTo(SalePayment::class);
    }

    public function exchangePayment(): BelongsTo
    {
        return $this->belongsTo(ExchangePayment::class);
    }

    public function layawayPayment(): BelongsTo
    {
        return $this->belongsTo(LayawayPayment::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function purchasePayment(): BelongsTo
    {
        return $this->belongsTo(PurchasePayment::class);
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(CashMovement::class, 'reversal_of_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(CashMovement::class, 'reversal_of_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
