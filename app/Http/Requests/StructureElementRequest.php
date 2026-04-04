<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class StructureElementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $elementoId = $this->route('id');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'modelo_estructura_id' => $isUpdate
                ? 'sometimes|exists:MODELO_ESTRUCTURA,modelo_estructura_id'
                : [
                    'required',
                    'exists:MODELO_ESTRUCTURA,modelo_estructura_id',
                    function ($attribute, $value, $fail) {
                        $tipo = DB::table('MODELO_ESTRUCTURA')
                            ->where('modelo_estructura_id', $value)
                            ->value('tipo');
                        if ($tipo !== 'elemento_flexible') {
                            $fail('El modelo de estructura debe ser de tipo elemento_flexible para crear elementos.');
                        }
                    },
                ],
            'padre_id' => [
                'nullable',
                'exists:ELEMENTO,elemento_id',
                // Evitar que un elemento sea su propio padre en UPDATE
                $isUpdate ? Rule::notIn([$elementoId]) : '',
                // El padre debe pertenecer al mismo modelo_estructura_id
                function ($attribute, $value, $fail) use ($isUpdate, $elementoId) {
                    if ($value === null) {
                        return; // raíz, sin padre, válido
                    }

                    // Obtener el modelo del padre
                    $modeloPadre = DB::table('ELEMENTO')
                        ->where('elemento_id', $value)
                        ->value('modelo_estructura_id');

                    // Obtener el modelo del elemento actual
                    if ($isUpdate) {
                        // En update, modelo_estructura_id puede venir en el body o se toma del elemento existente
                        $modeloActual = $this->input('modelo_estructura_id')
                            ?? DB::table('ELEMENTO')->where('elemento_id', $elementoId)->value('modelo_estructura_id');
                    } else {
                        $modeloActual = $this->input('modelo_estructura_id');
                    }

                    if ($modeloPadre !== $modeloActual) {
                        $fail('El elemento padre debe pertenecer al mismo modelo de estructura.');
                    }
                },
            ],
            'tipo' => $isUpdate
                ? ['sometimes', 'required', 'string', 'max:30', 'regex:/^[A-Za-z\xC0-\xFF0-9 ]+$/']
                : ['required', 'string', 'max:30', 'regex:/^[A-Za-z\xC0-\xFF0-9 ]+$/'],
            'categoria'    => 'nullable|in:A,B,C,D',
            'nomenclatura' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9.\-_]+$/'],
            'descripcion'  => ['nullable', 'string', 'max:500', 'regex:/^[A-Za-z\xC0-\xFF0-9 .,\-:;()]+$/'],
            'activo'       => 'boolean',

            // ── HU-012 (escritura flexible) — Gap 4 ──────────────────────────────────
            // Campo opcional para crear ELEMENTO + EVIDENCIAs en una sola request
            // (Opción 2 del documento COMPARACION_OPCIONES_ELEMENTO_EVIDENCIA.md).
            //
            // Reglas:
            //  - Solo aplica en POST (create). En PUT/PATCH se ignora — las evidencias
            //    se gestionan individualmente por POST /estructura/evidencias.
            //  - Es opcional: si no viene o viene vacío, solo se crea el ELEMENTO.
            //  - Solo válido para modelo_estructura tipo 'elemento_flexible'. Si el
            //    modelo es 'tradicional', withValidator() devuelve error.
            //  - Cada item solo necesita nomenclatura y descripcion; el estado y
            //    elemento_id los asigna el servicio automáticamente.
            // ─────────────────────────────────────────────────────────────────────────
            'evidencias'                  => $isUpdate ? 'prohibited' : 'sometimes|nullable|array',
            'evidencias.*.nomenclatura'   => 'required_with:evidencias|string|max:20|regex:/^[A-Za-z0-9.\-_]+$/',
            'evidencias.*.descripcion'    => 'nullable|string|max:500|regex:/^[A-Za-zÀ-ÿ0-9 .,\-:;()]+$/',
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
            'modelo_estructura_id.required' => 'El modelo de estructura es obligatorio.',
            'modelo_estructura_id.exists'   => 'El modelo de estructura seleccionado no es válido.',
            'padre_id.exists'               => 'El elemento padre seleccionado no existe.',
            'padre_id.not_in'               => 'Un elemento no puede ser su propio padre.',
            'padre_id.same_model'           => 'El elemento padre debe pertenecer al mismo modelo de estructura.',
            'tipo.required'                 => 'El tipo es obligatorio.',
            'tipo.max'                      => 'El tipo no puede exceder 30 caracteres.',
            'tipo.regex'                    => 'El tipo solo puede contener letras, números y espacios (ej: area, subarea, pauta, nivel1).',
            'categoria.in'                  => 'La categoría debe ser A, B, C o D.',
            'nomenclatura.max'              => 'La nomenclatura no puede exceder 20 caracteres.',
            'nomenclatura.regex'            => 'La nomenclatura solo puede contener letras, números, puntos, guiones y guiones bajos (ej: AG-01, F1.1).',
            'descripcion.max'               => 'La descripción no puede exceder 500 caracteres.',
            'descripcion.regex'             => 'La descripción contiene caracteres no permitidos (no se permiten @, #, $, % u otros símbolos).',
            'activo.boolean'                => 'El campo activo debe ser verdadero o falso.',
            // Mensajes para evidencias[] embebidas
            'evidencias.prohibited'                 => 'No se pueden crear evidencias al actualizar un elemento. Use POST /estructura/evidencias.',
            'evidencias.*.nomenclatura.required_with' => 'Cada evidencia debe tener nomenclatura.',
            'evidencias.*.nomenclatura.max'            => 'La nomenclatura de evidencia no puede exceder 20 caracteres.',
            'evidencias.*.nomenclatura.regex'          => 'La nomenclatura de evidencia solo permite letras, números, puntos, guiones y guiones bajos.',
            'evidencias.*.descripcion.max'             => 'La descripción de evidencia no puede exceder 500 caracteres.',
            'evidencias.*.descripcion.regex'           => 'La descripción de evidencia contiene caracteres no permitidos.',
        ];
    }
}
