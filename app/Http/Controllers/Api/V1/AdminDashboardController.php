<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CompanyStatus;
use App\Enums\OfferStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Offer;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalSales = (float) Purchase::where('status', PurchaseStatus::Completed)->sum('total_amount');

        $totalEarnings = (float) PurchaseDetail::whereHas('purchase', function ($query) {
            $query->where('status', PurchaseStatus::Completed);
        })->join('offers', 'purchase_details.offer_id', '=', 'offers.id')
            ->join('companies', 'offers.company_id', '=', 'companies.id')
            ->select(DB::raw('SUM(purchase_details.unit_price * companies.commission_percentage / 100) as earnings'))
            ->value('earnings');

        $approvedCompaniesCount = Company::where('status', CompanyStatus::Approved)->count();
        $pendingCompaniesCount = Company::where('status', CompanyStatus::Pending)->count();
        
        $activeUsersCount = User::whereHas('role', function ($query) {
            $query->where('name', UserRole::User->value);
        })->count();
        
        $activeOffersCount = Offer::where('status', OfferStatus::Available)->count();

        // Chart data for last 6 months
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('Y-m'));
        }

        $chartData = $months->map(function ($month) {
            $startOfMonth = \Carbon\Carbon::parse($month)->startOfMonth();
            $endOfMonth = \Carbon\Carbon::parse($month)->endOfMonth();

            $sales = (float) Purchase::where('status', PurchaseStatus::Completed)
                ->whereBetween('purchased_at', [$startOfMonth, $endOfMonth])
                ->sum('total_amount');

            $earnings = (float) PurchaseDetail::whereHas('purchase', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where('status', PurchaseStatus::Completed)
                    ->whereBetween('purchased_at', [$startOfMonth, $endOfMonth]);
            })->join('offers', 'purchase_details.offer_id', '=', 'offers.id')
                ->join('companies', 'offers.company_id', '=', 'companies.id')
                ->select(DB::raw('SUM(purchase_details.unit_price * companies.commission_percentage / 100) as earnings'))
                ->value('earnings');

            return [
                'month' => strtoupper($startOfMonth->translatedFormat('M')),
                'sales' => $sales,
                'earnings' => $earnings,
            ];
        });

        return $this->apiResponse('Estadísticas del dashboard obtenidas correctamente.', [
            'totalSales' => $totalSales,
            'totalEarnings' => $totalEarnings,
            'approvedCompaniesCount' => $approvedCompaniesCount,
            'pendingCompaniesCount' => $pendingCompaniesCount,
            'activeUsersCount' => $activeUsersCount,
            'activeOffersCount' => $activeOffersCount,
            'chartData' => $chartData,
        ]);
    }
}
