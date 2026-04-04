<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para listar solicitudes de ampliación con paginación y filtros.
 * Sigue el estándar establecido por el equipo para endpoints de listado.
 */
class ExtensionTimeRequestListRequest extends FormRequest
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
            // Paginación estándar
            'page' => 'integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            
            // Filtros específicos RF-15
            'usuario_id' => 'integer|nullable|exists:USUARIO,usuario_id',
            'estado' => 'string|nullable|in:pendiente,aprobada,rechazada',
            'evidencia_asignacion_id' => 'integer|nullable|exists:EVIDENCIA_ASIGNACION,evidencia_asignacion_id',
            'fecha_desde' => 'date|nullable',
            'fecha_hasta' => 'date|nullable|after_or_equal:fecha_desde',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'El número de página debe ser un entero.',
            'page.min' => 'El número de página debe ser al menos 1.',
            'per_page.integer' => 'La cantidad de registros por página debe ser un entero.',
            'per_page.min' => 'Debe solicitar al menos 1 registro por página.',
            'per_page.max' => 'No puede solicitar más de 100 registros por página.',
            'usuario_id.exists' => 'El usuario especificado no existe.',
            'estado.in' => 'El estado debe ser uno de: pendiente, aprobada, rechazada, cancelada.',
            'evidencia_asignacion_id.exists' => 'La asignación de evidencia especificada no existe.',
            'fecha_desde.date' => 'La fecha desde debe ser una fecha válida.',
            'fecha_hasta.date' => 'La fecha hasta debe ser una fecha válida.',
            'fecha_hasta.after_or_equal' => 'La fecha hasta debe ser posterior o igual a la fecha desde.',
        ];
    }
}
