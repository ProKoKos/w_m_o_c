<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $id = $this->get('id') ?? '';
        return [
            'name' => 'required|unique:roles,name,' . $id,
        ];
    }
}
