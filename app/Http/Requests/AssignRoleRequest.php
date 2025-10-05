<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // luego se limita con can:admin.super
    }

    public function rules(): array
    {
        return [
            'role' => [
            'bail', 
            'required',
            'string',
            'max:64',
            // debe existir en la tabla roles, columna name, y con guard_name = 'api'
            Rule::exists('roles', 'name')->where(fn ($q) => $q->where('guard_name', 'api')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'El rol es requerido.',
            'role.string'   => 'El rol debe ser un texto válido.',
            'role.exists'   => 'El rol no existe o no pertenece al guard api.',
        ];
    }
}
