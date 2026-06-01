<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $cards = $user->cards()
            ->latest()
            ->get();

        return $this->apiResponse('Tarjetas obtenidas correctamente.', CardResource::collection($cards));
    }
}
