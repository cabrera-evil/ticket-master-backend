<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cardholder' => $this->cardholder,
            'last_four' => $this->last_four,
            'expiry' => $this->expiry,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
