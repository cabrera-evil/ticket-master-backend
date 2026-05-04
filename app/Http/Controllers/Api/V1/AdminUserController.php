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

        return $this->apiResponse('Administrador registrado correctamente.', new UserResource($admin), Response::HTTP_CREATED);
    }
}
