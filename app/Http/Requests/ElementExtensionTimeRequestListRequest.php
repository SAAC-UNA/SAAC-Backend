<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para listar solicitudes de ampliación de elemento (modelo flexible).
 */
class ElementExtensionTimeRequestListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'                   => 'integer|min:1',
            'per_page'               => 'nullable|integer|min:1|max:100',
            'usuario_id'             => 'integer|nullable|exists:USUARIO,usuario_id',
            'estado'                 => 'string|nullable|in:pendiente,aprobada,rechazada,cancelada',
            'elemento_asignacion_id' => 'integer|nullable|exists:ELEMENTO_ASIGNACION,elemento_asignacion_id',
            'fecha_desde'            => 'date|nullable',
            'fecha_hasta'            => 'date|nullable|after_or_equal:fecha_desde',
        ];
    }

    public function messages(): array
    {
        return [
            'estado.in'                      => 'El estado debe ser: pendiente, aprobada, rechazada o cancelada.',
            'elemento_asignacion_id.exists'  => 'La asignación de elemento especificada no existe.',
            'fecha_hasta.after_or_equal'     => 'La fecha hasta debe ser igual o posterior a fecha desde.',
        ];
    }
}
