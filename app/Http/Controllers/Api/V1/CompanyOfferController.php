<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreOfferRequest;
use App\Http\Requests\Company\UpdateOfferRequest;
use App\Http\Resources\CompanyOfferResource;
use App\Models\Company;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyOfferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        $offers = $company->offers()
            ->withCount('purchaseDetails as sold_count')
            ->with('category')
            ->latest()
            ->paginate(15);

        return $this->paginatedResponse(
            'Ofertas obtenidas correctamente.',
            $offers,
            CompanyOfferResource::collection($offers)
        );
    }

    public function store(StoreOfferRequest $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        $offer = $company->offers()->create($request->validated());
        $offer->load('category');
        $offer->loadCount('purchaseDetails as sold_count');

        return $this->apiResponse(
            'Oferta creada correctamente.',
            new CompanyOfferResource($offer),
            Response::HTTP_CREATED
        );
    }

    public function update(UpdateOfferRequest $request, Offer $offer): JsonResponse
    {
        $company = $this->resolveCompany($request);
        abort_if($offer->company_id !== $company->id, Response::HTTP_NOT_FOUND);

        $offer->update($request->validated());
        $offer->load('category');
        $offer->loadCount('purchaseDetails as sold_count');

        return $this->apiResponse('Oferta actualizada correctamente.', new CompanyOfferResource($offer));
    }

    public function destroy(Request $request, Offer $offer): JsonResponse
    {
        $company = $this->resolveCompany($request);
        abort_if($offer->company_id !== $company->id, Response::HTTP_NOT_FOUND);

        $offer->delete();

        return $this->apiResponse('Oferta eliminada correctamente.');
    }

    private function resolveCompany(Request $request): Company
    {
        $user = $request->user();
        if (! $user instanceof User) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $company = $user->company;

        abort_if($company === null, Response::HTTP_FORBIDDEN, 'No tienes una empresa asociada.');
        abort_if(
            $company->status !== CompanyStatus::Approved,
            Response::HTTP_FORBIDDEN,
            'Tu empresa aún no ha sido aprobada.'
        );

        return $company;
    }
}
