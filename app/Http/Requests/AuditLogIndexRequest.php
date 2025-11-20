<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogIndexRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para esta petición.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para los filtros de búsqueda.
     */
    public function rules(): array
    {
        return [
            'usuario_id' => 'nullable|integer|exists:USUARIO,usuario_id',
            'tipo_accion_id' => 'nullable|integer|exists:TIPO_ACCION,tipo_accion_id',
            'tipo_accion' => 'nullable|string|exists:TIPO_ACCION,descripcion',
            'modulo' => 'nullable|string|max:100',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
        ];
    }
}
