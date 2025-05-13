<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $id = $this->get('id') ?? '';

        return [
            'name'     => 'required|min:2',
            'email'    => 'required|email|unique:users,email,' . $id,
            'password' => $this->isMethod('POST') ? 'required|min:6' : 'nullable|min:6',
        ];
    }
}
