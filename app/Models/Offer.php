<?php

namespace App\Models;

use App\Enums\OfferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    protected $fillable = [
        'company_id',
        'title',
        'regular_price',
        'offer_price',
        'starts_at',
        'ends_at',
        'redeemable_until',
        'coupon_limit',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'regular_price' => 'decimal:2',
            'offer_price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'redeemable_until' => 'datetime',
            'status' => OfferStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseDetails(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }
}
