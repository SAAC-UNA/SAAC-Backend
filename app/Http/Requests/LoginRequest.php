<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Permitir acceso público al endpoint de login
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cedula' => [
                'required',
                'string',
                'min:9',
                'max:20',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cedula.required' => 'La cédula es obligatoria',
            'cedula.string' => 'La cédula debe ser una cadena de texto',
            'cedula.min' => 'La cédula debe tener al menos 9 caracteres',
            'cedula.max' => 'La cédula no puede tener más de 20 caracteres',
            'password.required' => 'La contraseña es obligatoria',
            'password.string' => 'La contraseña debe ser una cadena de texto',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
        ];
    }
}
