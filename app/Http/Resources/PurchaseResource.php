<?php

namespace App\Http\Resources;

use App\Models\CouponCode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $coupons = CouponCode::query()
            ->with(['purchaseDetail.offer.company'])
            ->whereHas('purchaseDetail', fn ($q) => $q->where('purchase_id', $this->id))
            ->get();

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice?->invoice_number,
            'status' => $this->status->value,
            'total_amount' => (float) $this->total_amount,
            'purchased_at' => $this->purchased_at?->toISOString(),
            'coupons' => PurchaseCouponResource::collection($coupons),
        ];
    }
}
