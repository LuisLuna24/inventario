<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cash_register_id', 'business_date', 'opened_by', 'opened_at', 'opening_amount', 'status', 'closed_by', 'closed_at', 'late_close_authorized_by', 'late_close_reason', 'expected_cash', 'counted_cash', 'cash_difference', 'notes'])]
class CashSession extends Model
{
    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'opened_at' => 'datetime',
            'opening_amount' => 'decimal:2',
            'closed_at' => 'datetime',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
        ];
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function lateCloseAuthorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'late_close_authorized_by');
    }

    public function cashCounts(): HasMany
    {
        return $this->hasMany(CashCount::class);
    }

    public function salePayments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }
}
