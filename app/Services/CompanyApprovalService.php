<?php

namespace App\Services;

use App\Enums\CompanyApprovalAction;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyApprovalService
{
    public function approve(Company $company, User $admin, float $commissionPercentage): Company
    {
        if ($company->status !== CompanyStatus::Pending) {
            throw ValidationException::withMessages([
                'company' => ['Solo se pueden aprobar empresas pendientes.'],
            ]);
        }

        return DB::transaction(function () use ($company, $admin, $commissionPercentage): Company {
            $company->forceFill([
                'status' => CompanyStatus::Approved,
                'commission_percentage' => $commissionPercentage,
                'approved_at' => now(),
                'rejected_at' => null,
            ])->save();

            $company->approvals()->create([
                'approved_by' => $admin->id,
                'action' => CompanyApprovalAction::Approved,
                'commission_percentage' => $commissionPercentage,
            ]);

            return $company->refresh()->load('user');
        });
    }

    public function reject(Company $company, User $admin, ?string $reason): Company
    {
        if ($company->status !== CompanyStatus::Pending) {
            throw ValidationException::withMessages([
                'company' => ['Solo se pueden rechazar empresas pendientes.'],
            ]);
        }

        return DB::transaction(function () use ($company, $admin, $reason): Company {
            $company->forceFill([
                'status' => CompanyStatus::Rejected,
                'commission_percentage' => null,
                'approved_at' => null,
                'rejected_at' => now(),
            ])->save();

            $company->approvals()->create([
                'approved_by' => $admin->id,
                'action' => CompanyApprovalAction::Rejected,
                'reason' => $reason,
            ]);

            return $company->refresh()->load('user');
        });
    }
}
