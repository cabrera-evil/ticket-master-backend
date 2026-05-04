<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthRegistrationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminUserController extends Controller
{
    public function __construct(private readonly AuthRegistrationService $registrationService) {}

    public function store(StoreAdminRequest $request): JsonResponse
    {
        $admin = $this->registrationService->createAdmin($request->validated());

        return response()->json([
            'message' => 'Administrador registrado correctamente.',
            'data' => new UserResource($admin),
        ], Response::HTTP_CREATED);
    }
}
