<?php

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->user()?->company;

        if ($company === null || $company->status !== CompanyStatus::Approved) {
            return response()->json([
                'message' => 'La empresa debe estar aprobada para realizar esta accion.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
