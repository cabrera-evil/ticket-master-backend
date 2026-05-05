<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email', 'unique:companies,email'],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'name' => ['required', 'string', 'max:150'],
            'nit' => ['required', 'string', 'max:30', 'unique:companies,nit'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,30}$/'],
        ];
    }
}
