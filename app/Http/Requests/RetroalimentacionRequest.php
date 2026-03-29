<?php

namespace App\Http\Requests;

use App\Models\Evidence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * RetroalimentacionRequest — HU-013: Retroalimentación de Evidencias
 *
 * Valida el cuerpo del PATCH /api/evidencias/{id}/retroalimentacion
 * Solo puede ser enviado por roles autorizados (validado en el controller).
 *
 * Campos esperados en el body JSON:
 *   - estado    : string requerido, solo 'observada' o 'validada'
 *   - comentario: string requerido, hasta 2000 caracteres
 */
class RetroalimentacionRequest extends FormRequest
{
    /**
     * authorize() — ¿Puede este usuario hacer este request en absoluto?
     * Retorna true aquí porque la verificación de rol la hacemos en el controller
     * con lógica más específica (mensaje de error personalizado).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * rules() — Reglas de validación del body JSON.
     *
     * 'estado' solo acepta 'observada' o 'validada' (subconjunto del enum
     * completo de Evidence::ESTADOS). Un encargado no puede poner 'aprobado'
     * o 'rechazado' desde este endpoint — para eso ya existe EvidenceRequest.
     */
    public function rules(): array
    {
        return [
            'estado'     => ['required', 'string', Rule::in(['Observada', 'Validada'])],
            'comentario' => ['required', 'string', 'min:5', 'max:800'],
        ];
    }

    /**
     * messages() — Mensajes de error amigables en español.
     */
    public function messages(): array
    {
        return [
            'estado.required'     => 'El estado es requerido.',
            'estado.in'           => 'El estado debe ser "Observada" o "Validada".',
            'comentario.required' => 'El comentario es requerido.',
            'comentario.min'      => 'El comentario debe tener al menos 5 caracteres.',
            'comentario.max'      => 'El comentario no puede superar los 800 caracteres.',
        ];
    }
}
