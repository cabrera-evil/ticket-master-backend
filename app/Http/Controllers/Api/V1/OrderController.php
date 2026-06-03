<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\CartItem;
use App\Models\CouponCode;
use App\Models\Invoice;
use App\Models\PaymentSimulation;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $validated = $request->validate([
            'card_id'     => ['sometimes', 'integer'],
            'cardholder'  => ['required_without:card_id', 'string', 'min:2', 'max:100'],
            'card_number' => ['required_without:card_id', 'string'],
            'expiry'      => ['required_without:card_id', 'string', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'cvv'         => ['required_without:card_id', 'string', 'regex:/^\d{3,4}$/'],
            'save_card'   => ['sometimes', 'boolean'],
        ]);

        $savedCard = null;
        if (isset($validated['card_id'])) {
            $savedCard = Card::query()
                ->where('user_id', $user->id)
                ->find($validated['card_id']);

            if (! $savedCard instanceof Card) {
                return response()->json([
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Tarjeta guardada inválida.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } else {
            $cardNumber = preg_replace('/\s+/', '', $validated['card_number']);
            if (! preg_match('/^\d{13,19}$/', $cardNumber)) {
                return response()->json([
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Número de tarjeta inválido.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $cartItems = CartItem::query()
            ->with('offer')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Tu carrito está vacío.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        foreach ($cartItems as $item) {
            $purchased = PurchaseDetail::query()
                ->whereHas('purchase', fn ($q) => $q->where('user_id', $user->id))
                ->where('offer_id', $item->offer_id)
                ->count();

            if ($purchased + $item->quantity > 5) {
                return response()->json([
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => "Superaste el límite de 5 cupones para \"{$item->offer->title}\".",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $totalAmount = round(
            $cartItems->sum(fn (CartItem $item) => (float) $item->offer->offer_price * $item->quantity),
            2
        );

        $saveCard = (bool) ($validated['save_card'] ?? false);

        $result = DB::transaction(function () use ($user, $cartItems, $totalAmount, $validated, $saveCard, $savedCard) {
            $now = Carbon::now();

            $card = $savedCard;
            if (! $card instanceof Card) {
                $cardNumber = preg_replace('/\s+/', '', $validated['card_number']);
                $card = Card::create([
                    'user_id'   => $saveCard ? $user->id : null,
                    'token'     => Str::uuid(),
                    'cardholder' => $validated['cardholder'],
                    'last_four' => substr($cardNumber, -4),
                    'expiry'    => $validated['expiry'],
                ]);
            }

            $purchase = Purchase::create([
                'user_id'      => $user->id,
                'status'       => 'completed',
                'total_amount' => $totalAmount,
                'purchased_at' => $now,
            ]);

            foreach ($cartItems as $cartItem) {
                for ($i = 0; $i < $cartItem->quantity; $i++) {
                    $detail = PurchaseDetail::create([
                        'purchase_id' => $purchase->id,
                        'offer_id'    => $cartItem->offer_id,
                        'unit_price'  => $cartItem->offer->offer_price,
                    ]);
                    CouponCode::create([
                        'purchase_detail_id' => $detail->id,
                        'code'               => Str::uuid(),
                    ]);
                }
            }

            $invoiceNumber = 'LC-'.$now->format('Ymd').'-'.strtoupper(Str::random(6));

            Invoice::create([
                'purchase_id'    => $purchase->id,
                'invoice_number' => $invoiceNumber,
                'issued_at'      => $now,
                'total_amount'   => $totalAmount,
            ]);

            PaymentSimulation::create([
                'purchase_id'  => $purchase->id,
                'card_id'      => $card->id,
                'simulated_at' => $now,
            ]);

            CartItem::query()
                ->whereIn('id', $cartItems->pluck('id'))
                ->delete();

            return [
                'order_number' => $invoiceNumber,
                'total_amount' => $totalAmount,
                'items_count'  => $cartItems->sum('quantity'),
            ];
        });

        return $this->apiResponse('Compra realizada exitosamente.', $result, Response::HTTP_CREATED);
    }
}
