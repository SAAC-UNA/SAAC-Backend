<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest 
{
    /**
     * Determina si el usuario está autorizado para hacer esta petición.
     */
    public function authorize(): bool
    {
        // Por ahora se permite; luego se puede restringir con el usuario

        return true;
    }

    /**
     * Reglas de validación para la creación/actualización de roles.
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
            'description' => 'nullable|string|max:255',
            'permissions'   => 'required|array|min:1',                 //aceptar arreglo
            'permissions.*' => 'exists:permissions,name', //cada permiso debe existir
        ];
    }

    /**
     * Mensajes personalizados para los errores de validación.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique'   => 'Ya existe un rol con este nombre.',
            'name.max'      => 'El nombre no puede superar los 255 caracteres.',
            'description.max' => 'La descripción no puede superar los 255 caracteres.',
            'name.regex'    => 'El nombre solo puede contener letras y espacios, sin caracteres especiales.',
            'description.max'  => 'La descripción no puede superar los 255 caracteres.',
            'permissions.required' => 'Debe seleccionar al menos un permiso.',
            'permissions.array'    => 'El formato de los permisos no es válido.',
            'permissions.min'      => 'Debe elegir al menos un permiso.',
            'permissions.*.exists' => 'Se han enviado permisos inválidos.',
            
        ];
    }
//required = el campo ni siquiera llegó en el request.
//min = el campo sí llegó, pero está vacío ([]).
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