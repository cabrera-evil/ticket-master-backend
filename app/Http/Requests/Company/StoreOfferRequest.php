<?php

namespace App\Http\Requests\Company;

use App\Enums\OfferStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:200'],
            'description'      => ['required', 'string'],
            'category_id'      => ['required', 'integer', 'exists:categories,id'],
            'regular_price'    => ['required', 'numeric', 'min:0.01'],
            'offer_price'      => ['required', 'numeric', 'min:0.01', 'lt:regular_price'],
            'starts_at'        => ['required', 'date'],
            'ends_at'          => ['required', 'date', 'after:starts_at'],
            'redeemable_until' => ['required', 'date', 'after_or_equal:ends_at'],
            'coupon_limit'     => ['nullable', 'integer', 'min:1'],
            'image_url'        => ['nullable', 'string', 'url', 'max:500'],
            'status'           => ['required', Rule::enum(OfferStatus::class)],
        ];
    }
}
