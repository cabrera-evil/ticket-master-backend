<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveCompanyRequest;
use App\Http\Requests\Admin\RejectCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\CompanyApprovalService;
use Illuminate\Http\JsonResponse;

class AdminCompanyController extends Controller
{
    public function __construct(private readonly CompanyApprovalService $approvalService) {}

    public function pending(): JsonResponse
    {
        $companies = Company::query()
            ->with('user')
            ->where('status', CompanyStatus::Pending)
            ->latest()
            ->paginate(15);

        return $this->paginatedResponse(
            'Empresas pendientes obtenidas correctamente.',
            $companies,
            CompanyResource::collection($companies)
        );
    }

    public function approve(ApproveCompanyRequest $request, Company $company): JsonResponse
    {
        $validated = $request->validated();

        $company = $this->approvalService->approve(
            $company,
            $request->user(),
            (float) $validated['commission_percentage']
        );

        return $this->apiResponse('Empresa aprobada correctamente.', new CompanyResource($company));
    }

    public function reject(RejectCompanyRequest $request, Company $company): JsonResponse
    {
        $validated = $request->validated();

        $company = $this->approvalService->reject(
            $company,
            $request->user(),
            $validated['reason'] ?? null
        );

        return $this->apiResponse('Empresa rechazada correctamente.', new CompanyResource($company));
    }
}
