<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogExportRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a hacer esta petición.
     */
    public function authorize(): bool
    {
        return true; // Ya está protegido por middleware/roles
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'format' => 'nullable|in:pdf,excel',
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'fecha_desde.required' => 'Debe indicar la fecha inicial del rango.',
            'fecha_desde.date' => 'La fecha inicial debe ser una fecha válida.',
            
            'fecha_hasta.required' => 'Debe indicar la fecha final del rango.',
            'fecha_hasta.date' => 'La fecha final debe ser una fecha válida.',
            'fecha_hasta.after_or_equal' => 'La fecha final debe ser posterior o igual a la inicial.',

            'format.in' => 'El formato debe ser pdf o excel.',
        ];
    }
}
