<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para listar informes de acreditación publicados (endpoint público).
 *
 * HU-027: parámetros de filtrado y paginación del índice público.
 */
class ListAccreditationReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // endpoint público, sin restricción de autenticación
    }

    public function rules(): array
    {
        return [
            'carrera_id'        => ['sometimes', 'integer', 'min:1'],
            'sede_id'           => ['sometimes', 'integer', 'min:1'],
            'carrera_campus_id' => ['sometimes', 'integer', 'min:1'],
            'per_page'          => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
