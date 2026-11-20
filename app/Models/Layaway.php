<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['warehouse_id', 'customer_id', 'code', 'status', 'total', 'paid_total', 'expires_at', 'completed_at', 'cancelled_at', 'cancellation_reason', 'created_by', 'cancel_authorized_by'])]
class Layaway extends Model
{
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'paid_total' => 'decimal:2',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelAuthorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancel_authorized_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LayawayItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LayawayPayment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(LayawayEvent::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }
}
