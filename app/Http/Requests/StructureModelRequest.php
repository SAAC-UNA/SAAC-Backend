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

    protected function prepareForValidation(): void
    {
        if ($this->has('activo')) {
            $val = $this->input('activo');
            if (is_string($val)) {
                $this->merge(['activo' => filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
            }
        }
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        $modelId = $this->route('id');

        if ($isUpdate) {
            // tipo  → NO editable: cambiar tradicional⇔elemento_flexible rompe la estructura asociada
            // activo → NO editable aquí: usar PATCH /active
            return [
                'nombre'      => ['sometimes', 'string', 'max:100', 'regex:/^[A-Za-z\xC0-\xFF0-9 .\-]+$/u',
                                  Rule::unique('MODELO_ESTRUCTURA', 'nombre')->ignore($modelId, 'modelo_estructura_id')],
                'descripcion' => ['nullable', 'string', 'max:500', 'regex:/^[A-Za-z\xC0-\xFF0-9 .,\-:;()]+$/u'],
                'version'     => ['nullable', 'string', 'max:20',  'regex:/^[A-Za-z\xC0-\xFF0-9.\-]+$/u'],
                'tipos_jerarquia' => ['nullable', 'array'],
                'tipos_jerarquia.*.tipo'       => ['required_with:tipos_jerarquia', 'string', 'max:50'],
                'tipos_jerarquia.*.padre_tipo' => ['nullable', 'string', 'max:50'],
                'tipos_asignables'    => ['nullable', 'array'],
                'tipos_asignables.*'  => ['string', 'max:50'],
            ];
        }

        return [
            'nombre'      => ['required', 'string', 'max:100', 'regex:/^[A-Za-z\xC0-\xFF0-9 .\-]+$/u',
                              Rule::unique('MODELO_ESTRUCTURA', 'nombre')],
            'tipo'        => ['required', Rule::in(['elemento_flexible'])],
            'descripcion' => ['nullable', 'string', 'max:500', 'regex:/^[A-Za-z\xC0-\xFF0-9 .,\-:;()]+$/u'],
            'version'     => ['nullable', 'string', 'max:20',  'regex:/^[A-Za-z\xC0-\xFF0-9.\-]+$/u'],
            'activo'      => 'boolean',
            'tipos_jerarquia' => ['nullable', 'array'],
            'tipos_jerarquia.*.tipo'       => ['required_with:tipos_jerarquia', 'string', 'max:50'],
            'tipos_jerarquia.*.padre_tipo' => ['nullable', 'string', 'max:50'],
            'tipos_asignables'    => ['nullable', 'array'],
            'tipos_asignables.*'  => ['string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'   => 'El nombre es obligatorio.',
            'nombre.max'        => 'El nombre no puede exceder 100 caracteres.',
            'nombre.regex'      => 'El nombre solo puede contener letras, números, espacios, puntos y guiones.',
            'nombre.unique'     => 'Ya existe un modelo de estructura con ese nombre.',
            'tipo.required'     => 'El tipo de modelo es obligatorio.',
            'tipo.in'           => 'El único tipo de modelo que puede crear es: elemento_flexible. El modelo tradicional es gestionado por el sistema.',
            'version.max'       => 'La versión no puede exceder 20 caracteres.',
            'version.regex'     => 'La versión solo puede contener letras, números, puntos y guiones (ej: 2026, 2018.1).',
            'descripcion.max'   => 'La descripción no puede exceder 500 caracteres.',
            'descripcion.regex' => 'La descripción contiene caracteres no permitidos (no se permiten @, #, $, % u otros símbolos).',
            'activo.boolean'    => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
