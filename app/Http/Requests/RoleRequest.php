<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

/**
 * Validation class for creating and updating Roles.
 * Ensures business rules are respected before passing data to the controller.
 */
class RoleRequest extends FormRequest
{
    /**
     * Determines if the user is authorized to make this request.
     * 
     * @return bool Always returns true for now. 
     *              In the future, this can restrict access based on user roles/permissions.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating or updating roles.
     * 
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Solo permite letras (con o sin tildes), ñ/Ñ y espacios
                'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/',
                // Unique constraint with exception on update
                Rule::unique('roles', 'name')->ignore($this->route('id')),
            ],
            'description'   => 'nullable|string|max:255',
            'permissions'   => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name',
        ];
    }

    /**
     * Custom validation messages (in Spanish for end users).
     * 
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique'   => 'Ya existe un rol con este nombre.',
            'name.max'      => 'El nombre no puede superar los 255 caracteres.',
            'name.regex'    => 'El nombre solo puede contener letras y espacios, sin caracteres especiales.',
            
            'description.max'  => 'La descripción no puede superar los 255 caracteres.',
            'permissions.required' => 'Debe seleccionar al menos un permiso.',
            'permissions.array'    => 'El formato de los permisos no es válido.',
            'permissions.min'      => 'Debe elegir al menos un permiso.',
            'permissions.*.exists' => 'Se han enviado permisos inválidos.',
        ];
    }

    /**
     * Override the default validation error response to keep API format consistent.
     * Keys are in English, but messages remain in Spanish for the user.
     */
    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'error'   => 'Validation Error',
            'errors'  => $validator->errors(), // mensajes siguen en español
        ], 422);

        throw new ValidationException($validator, $response);
    }
}


