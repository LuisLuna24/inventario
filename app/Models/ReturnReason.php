<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'allows_refund', 'is_active'])]
class ReturnReason extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'allows_refund' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function saleReturns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }
}
