<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cash_session_id', 'counted_by', 'counted_at', 'denomination', 'quantity', 'subtotal'])]
class CashCount extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'counted_at' => 'datetime',
            'denomination' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }
}
