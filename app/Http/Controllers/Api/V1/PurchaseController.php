<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $purchases = Purchase::query()
            ->with(['invoice'])
            ->where('user_id', $user->id)
            ->latest('purchased_at')
            ->get();

        return $this->apiResponse(
            'Compras obtenidas correctamente.',
            PurchaseResource::collection($purchases)
        );
    }
}
