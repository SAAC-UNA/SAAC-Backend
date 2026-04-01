<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FilterElementRequest extends FormRequest
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
        return [
            'tipo'                  => ['nullable', 'string'],
            'categoria'             => ['nullable', 'string'],
            'estado'                => ['nullable', 'string'],
            'activo'                => ['nullable', 'boolean'],
            'modelo_estructura_id'  => ['nullable', 'integer', 'exists:MODELO_ESTRUCTURA,modelo_estructura_id'],
            'padre_id'              => ['nullable', 'integer', 'exists:ELEMENTO,elemento_id'],
            'fecha_limite_desde'    => ['nullable', 'date', 'before_or_equal:fecha_limite_hasta'],
            'fecha_limite_hasta'    => ['nullable', 'date', 'after_or_equal:fecha_limite_desde'],
            'ciclo_acreditacion_id' => ['nullable', 'integer', 'exists:CICLO_ACREDITACION,ciclo_acreditacion_id'],
            'rol_id'                => ['nullable', 'integer', 'exists:roles,id'],
            'busqueda'              => ['nullable', 'string', 'min:3', 'max:200'],
            'sort_by'               => ['nullable', 'string', 'in:descripcion,nomenclatura,estado,fecha_limite,tipo'],
            'sort_order'            => ['nullable', 'string', 'in:asc,desc'],
            'per_page'              => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.string'                      => 'El tipo debe ser una cadena de texto.',
            'categoria.string'                 => 'La categoría debe ser una cadena de texto.',
            'estado.string'                    => 'El estado debe ser una cadena de texto.',
            'activo.boolean'                   => 'El campo activo debe ser verdadero o falso.',
            'modelo_estructura_id.integer'     => 'El ID del modelo de estructura debe ser un entero.',
            'modelo_estructura_id.exists'      => 'El modelo de estructura especificado no existe.',
            'padre_id.integer'                 => 'El ID del elemento padre debe ser un entero.',
            'padre_id.exists'                  => 'El elemento padre especificado no existe.',
            'fecha_limite_desde.date'          => 'La fecha límite desde debe ser una fecha válida.',
            'fecha_limite_desde.before_or_equal' => 'La fecha límite desde debe ser anterior o igual a fecha límite hasta.',
            'fecha_limite_hasta.date'          => 'La fecha límite hasta debe ser una fecha válida.',
            'fecha_limite_hasta.after_or_equal' => 'La fecha límite hasta debe ser posterior o igual a fecha límite desde.',
            'ciclo_acreditacion_id.integer'    => 'El ID del ciclo de acreditación debe ser un entero.',
            'ciclo_acreditacion_id.exists'     => 'El ciclo de acreditación especificado no existe.',
            'rol_id.integer'                   => 'El ID del rol debe ser un entero.',
            'rol_id.exists'                    => 'El rol especificado no existe.',
            'busqueda.min'                     => 'La búsqueda debe tener al menos 3 caracteres (requerido por el índice FULLTEXT).',
            'busqueda.max'                     => 'La búsqueda no puede superar los 200 caracteres.',
            'sort_by.in'                       => 'El campo de ordenamiento debe ser: descripcion, nomenclatura, estado, fecha_limite o tipo.',
            'sort_order.in'                    => 'El orden debe ser asc o desc.',
            'per_page.integer'                 => 'El número de elementos por página debe ser un entero.',
            'per_page.min'                     => 'El mínimo de elementos por página es 5.',
            'per_page.max'                     => 'El máximo de elementos por página es 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo'                  => 'tipo de elemento',
            'categoria'             => 'categoría',
            'estado'                => 'estado',
            'activo'                => 'activo',
            'modelo_estructura_id'  => 'modelo de estructura',
            'padre_id'              => 'elemento padre',
            'fecha_limite_desde'    => 'fecha límite desde',
            'fecha_limite_hasta'    => 'fecha límite hasta',
            'ciclo_acreditacion_id' => 'ciclo de acreditación',
            'rol_id'                => 'rol',
            'busqueda'              => 'búsqueda de texto',
            'sort_by'               => 'campo de ordenamiento',
            'sort_order'            => 'orden',
            'per_page'              => 'elementos por página',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Error de validación en los filtros de elementos.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
