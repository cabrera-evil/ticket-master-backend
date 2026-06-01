<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CompanyStatus;
use App\Enums\OfferStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FeaturedOfferResource;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function featured(): JsonResponse
    {
        $offers = $this->availableOffersQuery()
            ->where('is_featured', true)
            ->orderBy('featured_sort_order')
            ->orderByDesc('created_at')
            ->get();

        return $this->apiResponse('Ofertas destacadas obtenidas correctamente.', FeaturedOfferResource::collection($offers));
    }

    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $searchTerm = '%'.strtolower(addcslashes($search, '%_\\')).'%';

        $offers = $this->availableOffersQuery()
            ->when($search !== '', function ($query) use ($searchTerm): void {
                $query->where(function ($query) use ($searchTerm): void {
                    $query
                        ->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$searchTerm])
                        ->orWhereHas('company', function ($query) use ($searchTerm): void {
                            $query->whereRaw('LOWER(name) LIKE ?', [$searchTerm]);
                        })
                        ->orWhereHas('category', function ($query) use ($searchTerm): void {
                            $query
                                ->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
                                ->orWhereRaw('LOWER(slug) LIKE ?', [$searchTerm]);
                        });
                });
            })
            ->orderByDesc('is_featured')
            ->orderBy('featured_sort_order')
            ->orderByDesc('created_at')
            ->get();

        return $this->apiResponse('Ofertas obtenidas correctamente.', FeaturedOfferResource::collection($offers));
    }

    private function availableOffersQuery(): Builder
    {
        return Offer::query()
            ->with(['company', 'category'])
            ->where('status', OfferStatus::Available)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereHas('company', function ($query): void {
                $query->where('status', CompanyStatus::Approved);
            });
    }
}
