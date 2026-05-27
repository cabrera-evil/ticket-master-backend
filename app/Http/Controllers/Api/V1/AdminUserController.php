<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthRegistrationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminUserController extends Controller
{
    public function __construct(private readonly AuthRegistrationService $registrationService) {}

    public function index(): JsonResponse
    {
        $users = User::query()
            ->with(['role', 'client', 'company'])
            ->latest()
            ->paginate(15);

        return $this->paginatedResponse(
            'Usuarios obtenidos correctamente.',
            $users,
            UserResource::collection($users)
        );
    }

    public function store(StoreAdminRequest $request): JsonResponse
    {
        $admin = $this->registrationService->createAdmin($request->validated());

        return $this->apiResponse('Administrador registrado correctamente.', new UserResource($admin), Response::HTTP_CREATED);
    }
}
