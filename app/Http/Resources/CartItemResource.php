<?php

namespace App\Http\Resources;

use App\Models\PurchaseDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $offer = $this->whenLoaded('offer');
        $unitPrice = $this->offer ? (float) $this->offer->offer_price : 0.0;

        $purchasedCount = PurchaseDetail::query()
            ->whereHas('purchase', fn ($q) => $q->where('user_id', $this->user_id))
            ->where('offer_id', $this->offer_id)
            ->count();

        return [
            'id' => $this->id,
            'offer_id' => $this->offer_id,
            'quantity' => $this->quantity,
            'unit_price' => $unitPrice,
            'total_price' => round($unitPrice * $this->quantity, 2),
            'purchased_count' => $purchasedCount,
            'offer' => new FeaturedOfferResource($offer),
        ];
    }
}
