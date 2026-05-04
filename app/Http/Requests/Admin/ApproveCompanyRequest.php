<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
