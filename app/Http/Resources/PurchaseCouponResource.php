<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseCouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $offer = $this->purchaseDetail->offer;
        $regularPrice = (float) $offer->regular_price;
        $offerPrice = (float) $offer->offer_price;
        $discount = $regularPrice > 0
            ? (int) round((($regularPrice - $offerPrice) / $regularPrice) * 100)
            : 0;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'unit_price' => (float) $this->purchaseDetail->unit_price,
            'redeemable_until' => $offer->redeemable_until?->toISOString(),
            'offer' => [
                'id' => $offer->id,
                'title' => $offer->title,
                'merchant' => $offer->company?->name,
                'image_url' => $offer->image_url,
                'regular_price' => $regularPrice,
                'offer_price' => $offerPrice,
                'discount' => "-{$discount}%",
            ],
        ];
    }
}
