<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CompanyStatus;
use App\Enums\OfferStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartItemResource;
use App\Models\CartItem;
use App\Models\Offer;
use App\Models\PurchaseDetail;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $items = CartItem::query()
            ->with(['offer.company', 'offer.category'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return $this->apiResponse('Carrito obtenido correctamente.', [
            'items' => CartItemResource::collection($items),
            'summary' => $this->summary($items),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'offer_id' => ['required', 'integer'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        $offer = $this->availableOffersQuery()
            ->whereKey($validated['offer_id'])
            ->firstOrFail();

        $item = CartItem::query()->firstOrNew([
            'user_id' => $user->id,
            'offer_id' => $offer->id,
        ]);

        $purchased = PurchaseDetail::query()
            ->whereHas('purchase', fn ($q) => $q->where('user_id', $user->id))
            ->where('offer_id', $offer->id)
            ->count();

        $remaining = max(0, 5 - $purchased);

        if ($remaining === 0) {
            return response()->json([
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Ya alcanzaste el límite de 5 cupones para esta oferta.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $current = $item->exists ? $item->quantity : 0;
        $item->quantity = min($remaining, $current + ($validated['quantity'] ?? 1));
        $item->save();
        $item->load(['offer.company', 'offer.category']);

        return $this->apiResponse('Oferta agregada al carrito correctamente.', new CartItemResource($item), Response::HTTP_CREATED);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorizeCartItem($cartItem, $user);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $purchased = PurchaseDetail::query()
            ->whereHas('purchase', fn ($q) => $q->where('user_id', $user->id))
            ->where('offer_id', $cartItem->offer_id)
            ->count();

        $remaining = max(0, 5 - $purchased);

        if ($validated['quantity'] > $remaining) {
            return response()->json([
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => "Solo puedes comprar {$remaining} cupón(es) más de esta oferta.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);
        $cartItem->load(['offer.company', 'offer.category']);

        return $this->apiResponse('Carrito actualizado correctamente.', new CartItemResource($cartItem));
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->authorizeCartItem($cartItem, $user);

        $cartItem->delete();

        return $this->apiResponse('Oferta eliminada del carrito correctamente.');
    }

    private function availableOffersQuery(): Builder
    {
        return Offer::query()
            ->with(['company', 'category'])
            ->where('status', OfferStatus::Available)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereHas('company', function ($query): void {
                $query->where('status', CompanyStatus::Approved);
            });
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $user;
    }

    private function authorizeCartItem(CartItem $cartItem, User $user): void
    {
        abort_if($cartItem->user_id !== $user->id, Response::HTTP_NOT_FOUND);
    }

    private function summary($items): array
    {
        $subtotal = $items->sum(fn (CartItem $item): float => (float) $item->offer->offer_price * $item->quantity);

        return [
            'items_count' => $items->sum('quantity'),
            'subtotal' => round($subtotal, 2),
        ];
    }
}
