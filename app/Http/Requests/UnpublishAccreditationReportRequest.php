<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para despublicar un informe de acreditación.
 *
 * HU-028: Solo se permite si el informe está actualmente publicado.
 * La autorización de rol se delega a AccreditationReportPolicy::unpublish().
 *
 * El único campo del body es el motivo (opcional).
 * El informe llega como parámetro de ruta: /api/reports/{report}/unpublish
 */
class UnpublishAccreditationReportRequest extends FormRequest
{
    // La autorización se delega a AccreditationReportPolicy::unpublish()
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Motivo de la despublicación (recomendado, no obligatorio)
            'motivo' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.max' => 'El motivo no puede superar los 500 caracteres.',
        ];
    }
}
