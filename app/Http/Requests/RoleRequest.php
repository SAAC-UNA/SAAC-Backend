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
                Rule::unique('roles', 'name')->ignore($this->route('id')),
            ],
            'description' => 'nullable|string|max:255',
            'permissions'   => 'required|array|min:1',                 // ✅ aceptar arreglo
            'permissions.*' => 'exists:permissions,name', // ✅ cada permiso debe existir
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

            'permissions.required' => 'Debe seleccionar al menos un permiso.',
            'permissions.array'    => 'El formato de los permisos no es válido.',
            'permissions.min'      => 'Debe elegir al menos un permiso.',
            'permissions.*.exists' => 'Se han enviado permisos inválidos.',

            
        ];
    }
}
//required = el campo ni siquiera llegó en el request.
//min = el campo sí llegó, pero está vacío ([]).

