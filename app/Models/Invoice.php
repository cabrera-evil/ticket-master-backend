<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'purchase_id',
        'invoice_number',
        'issued_at',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
