<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponCode extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'purchase_detail_id',
        'code',
    ];

    public function purchaseDetail(): BelongsTo
    {
        return $this->belongsTo(PurchaseDetail::class);
    }
}
