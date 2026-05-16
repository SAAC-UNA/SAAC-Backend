<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cedula' => ['required', 'string', 'regex:/^\d{9}$/'],
            'role' => [
                'required',
                'string',
                'max:64',
                Rule::exists('roles', 'name')->where(
                    fn ($query) => $query->where('guard_name', 'api')
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cedula.required' => 'La cedula es requerida.',
            'cedula.regex' => 'La cedula debe contener exactamente 9 digitos.',
            'role.required' => 'El rol es requerido.',
            'role.exists' => 'El rol seleccionado no existe.',
        ];
    }
}
