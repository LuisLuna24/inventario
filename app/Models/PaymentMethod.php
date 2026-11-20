<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'is_cash', 'is_active'])]
class PaymentMethod extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_cash' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function layawayPayments(): HasMany
    {
        return $this->hasMany(LayawayPayment::class);
    }

    public function salePayments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function exchangePayments(): HasMany
    {
        return $this->hasMany(ExchangePayment::class);
    }
}
