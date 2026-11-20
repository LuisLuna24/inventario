<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'pressure', 'price_default', 'unity'])]
class VinilType extends Model
{
    protected function casts(): array
    {
        return [
            'pressure' => 'decimal:2',
            'price_default' => 'decimal:2',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(VinilPrice::class);
    }
}
