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
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
