<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CompanyStatus;
use App\Enums\OfferStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FeaturedOfferResource;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;

class OfferController extends Controller
{
    public function featured(): JsonResponse
    {
        $offers = Offer::query()
            ->with(['company', 'category'])
            ->where('status', OfferStatus::Available)
            ->where('is_featured', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereHas('company', function ($query): void {
                $query->where('status', CompanyStatus::Approved);
            })
            ->orderBy('featured_sort_order')
            ->orderByDesc('created_at')
            ->get();

        return $this->apiResponse('Ofertas destacadas obtenidas correctamente.', FeaturedOfferResource::collection($offers));
    }
}
