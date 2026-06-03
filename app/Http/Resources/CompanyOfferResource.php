<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $regularPrice = (float) $this->regular_price;
        $offerPrice = (float) $this->offer_price;
        $discount = $regularPrice > 0
            ? (int) round((($regularPrice - $offerPrice) / $regularPrice) * 100)
            : 0;

        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'regular_price'    => $regularPrice,
            'offer_price'      => $offerPrice,
            'discount'         => "-{$discount}%",
            'image_url'        => $this->image_url,
            'starts_at'        => $this->starts_at?->toISOString(),
            'ends_at'          => $this->ends_at?->toISOString(),
            'redeemable_until' => $this->redeemable_until?->toISOString(),
            'coupon_limit'     => $this->coupon_limit,
            'status'           => $this->status->value,
            'sold_count'       => (int) ($this->sold_count ?? 0),
            'category'         => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
