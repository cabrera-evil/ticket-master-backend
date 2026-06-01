<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSimulation extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'purchase_id',
        'card_id',
        'simulated_at',
    ];

    protected function casts(): array
    {
        return [
            'simulated_at' => 'datetime',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
