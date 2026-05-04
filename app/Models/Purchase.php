<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Purchase extends Model
{
    protected $fillable = [
        'client_id',
        'status',
        'total_amount',
        'purchased_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseStatus::class,
            'total_amount' => 'decimal:2',
            'purchased_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function paymentSimulation(): HasOne
    {
        return $this->hasOne(PaymentSimulation::class);
    }
}
