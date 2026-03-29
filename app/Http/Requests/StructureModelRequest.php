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

        $modeloId = $this->route('id');

        if ($isUpdate) {
            // tipo  → NO editable: cambiar tradicional⇔elemento_flexible rompe la estructura asociada
            // activo → NO editable aquí: usar PATCH /active
            return [
                'nombre'      => ['sometimes', 'string', 'max:100', 'regex:/^[A-Za-z\xC0-\xFF0-9 .\-]+$/',
                                  Rule::unique('MODELO_ESTRUCTURA', 'nombre')->ignore($modeloId, 'modelo_estructura_id')],
                'descripcion' => ['nullable', 'string', 'max:500', 'regex:/^[A-Za-z\xC0-\xFF0-9 .,\-:;()]+$/'],
                'version'     => ['nullable', 'string', 'max:20',  'regex:/^[A-Za-z0-9.\-]+$/'],
            ];
        }

        return [
            'nombre'      => ['required', 'string', 'max:100', 'regex:/^[A-Za-z\xC0-\xFF0-9 .\-]+$/',
                              Rule::unique('MODELO_ESTRUCTURA', 'nombre')],
            'tipo'        => ['required', Rule::in(['elemento_flexible'])],
            'descripcion' => ['nullable', 'string', 'max:500', 'regex:/^[A-Za-z\xC0-\xFF0-9 .,\-:;()]+$/'],
            'version'     => ['nullable', 'string', 'max:20',  'regex:/^[A-Za-z0-9.\-]+$/'],
            'activo'      => 'boolean',
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
