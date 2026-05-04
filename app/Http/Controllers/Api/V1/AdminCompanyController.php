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

        return response()->json([
            'message' => 'Empresas pendientes obtenidas correctamente.',
            'data' => CompanyResource::collection($companies),
        ]);
    }

    public function approve(ApproveCompanyRequest $request, Company $company): JsonResponse
    {
        $validated = $request->validated();

        $company = $this->approvalService->approve(
            $company,
            $request->user(),
            (float) $validated['commission_percentage']
        );

        return response()->json([
            'message' => 'Empresa aprobada correctamente.',
            'data' => new CompanyResource($company),
        ]);
    }

    public function reject(RejectCompanyRequest $request, Company $company): JsonResponse
    {
        $validated = $request->validated();

        $company = $this->approvalService->reject(
            $company,
            $request->user(),
            $validated['reason'] ?? null
        );

        return response()->json([
            'message' => 'Empresa rechazada correctamente.',
            'data' => new CompanyResource($company),
        ]);
    }
}
