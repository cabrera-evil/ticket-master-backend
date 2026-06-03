<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CompanyStatus;
use App\Enums\OfferStatus;
use App\Enums\PurchaseStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyDashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        $soldCoupons = PurchaseDetail::query()
            ->whereHas('offer', fn ($q) => $q->where('company_id', $company->id))
            ->whereHas('purchase', fn ($q) => $q->where('status', PurchaseStatus::Completed))
            ->count();

        $totalRevenue = (float) PurchaseDetail::query()
            ->whereHas('offer', fn ($q) => $q->where('company_id', $company->id))
            ->whereHas('purchase', fn ($q) => $q->where('status', PurchaseStatus::Completed))
            ->sum('unit_price');

        $activeOffersCount = $company->offers()
            ->where('status', OfferStatus::Available)
            ->count();

        $uniqueBuyers = Purchase::query()
            ->whereHas('details', fn ($q) => $q->whereHas('offer', fn ($q) => $q->where('company_id', $company->id)))
            ->where('status', PurchaseStatus::Completed)
            ->distinct('user_id')
            ->count('user_id');

        return $this->apiResponse('Estadísticas obtenidas correctamente.', [
            'soldCoupons'      => $soldCoupons,
            'totalRevenue'     => $totalRevenue,
            'activeOffersCount' => $activeOffersCount,
            'uniqueBuyers'     => $uniqueBuyers,
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        $purchases = Purchase::query()
            ->with([
                'user',
                'invoice',
                'details' => fn ($q) => $q
                    ->whereHas('offer', fn ($q) => $q->where('company_id', $company->id))
                    ->with(['offer', 'couponCode']),
            ])
            ->whereHas('details', fn ($q) => $q->whereHas('offer', fn ($q) => $q->where('company_id', $company->id)))
            ->where('status', PurchaseStatus::Completed)
            ->latest('purchased_at')
            ->get();

        $result = $purchases
            ->groupBy('user_id')
            ->map(function ($userPurchases) {
                $user = $userPurchases->first()->user;
                $totalSpent = $userPurchases->flatMap->details->sum('unit_price');

                return [
                    'user' => [
                        'id'       => $user->id,
                        'name'     => $user->name,
                        'username' => $user->username,
                        'email'    => $user->email,
                    ],
                    'purchases_count' => $userPurchases->count(),
                    'total_spent'     => round((float) $totalSpent, 2),
                    'invoices'        => $userPurchases->map(fn ($purchase) => [
                        'invoice_number' => $purchase->invoice?->invoice_number,
                        'purchased_at'   => $purchase->purchased_at?->toISOString(),
                        'coupons'        => $purchase->details->map(fn ($detail) => [
                            'code'        => $detail->couponCode?->code,
                            'offer_title' => $detail->offer?->title,
                            'unit_price'  => (float) $detail->unit_price,
                        ])->values(),
                    ])->values(),
                ];
            })
            ->values();

        return $this->apiResponse('Clientes obtenidos correctamente.', $result);
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
