<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeaturedOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $regularPrice = (float) $this->regular_price;
        $offerPrice = (float) $this->offer_price;
        $discount = $regularPrice > 0
            ? (int) round((($regularPrice - $offerPrice) / $regularPrice) * 100)
            : 0;

        return [
            'id' => $this->id,
            'merchant' => $this->company?->name,
            'title' => $this->title,
            'description' => $this->description,
            'discount' => "-{$discount}%",
            'regular_price' => $regularPrice,
            'offer_price' => $offerPrice,
            'image_url' => $this->image_url,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'redeemable_until' => $this->redeemable_until?->toISOString(),
            'coupon_limit' => $this->coupon_limit,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'merchant_details' => $this->whenLoaded('company', fn (): array => [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'address' => $this->company->address,
                'phone' => $this->company->phone,
                'email' => $this->company->email,
            ]),
        ];
    }
}
