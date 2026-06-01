<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $offer = $this->whenLoaded('offer');
        $unitPrice = $this->offer ? (float) $this->offer->offer_price : 0.0;

        return [
            'id' => $this->id,
            'offer_id' => $this->offer_id,
            'quantity' => $this->quantity,
            'unit_price' => $unitPrice,
            'total_price' => round($unitPrice * $this->quantity, 2),
            'offer' => new FeaturedOfferResource($offer),
        ];
    }
}
