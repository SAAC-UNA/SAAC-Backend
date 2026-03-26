<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StructureModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        if ($isUpdate) {
            // tipo  → NO editable: cambiar tradicional⇔elemento_flexible rompe la estructura asociada
            // activo → NO editable aquí: usar PATCH /toggle
            return [
                'nombre'      => 'sometimes|string|max:100',
                'descripcion' => 'nullable|string',
                'version'     => 'nullable|string|max:20',
            ];
        }

        return [
            'nombre'      => 'required|string|max:100',
            'tipo'        => [
                'required',
                Rule::in(['elemento_flexible']),
            ],
            'descripcion' => 'nullable|string',
            'version'     => 'nullable|string|max:20',
            'activo'      => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'  => 'El nombre es obligatorio.',
            'nombre.max'       => 'El nombre no puede exceder 100 caracteres.',
            'tipo.required'    => 'El tipo de modelo es obligatorio.',
            'tipo.in'          => 'El único tipo de modelo que puede crear es: elemento_flexible. El modelo tradicional es gestionado por el sistema.',
            'version.max'      => 'La versión no puede exceder 20 caracteres.',
            'activo.boolean'   => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
