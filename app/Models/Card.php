<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Card extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'cardholder',
        'last_four',
        'expiry',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentSimulation(): HasOne
    {
        return $this->hasOne(PaymentSimulation::class);
    }
}
