<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\AccreditationReport;

/**
 * Request para editar el informe de acreditación de un ciclo.
 *
 * HU-027: Publicación de informe de acreditación aprobado.
 *
 * Permite corregir los datos de un informe ya publicado o despublicado.
 * Todos los campos son opcionales (PATCH semántico).
 */
class UpdateAccreditationReportRequest extends FormRequest
{
    // La autorización se delega a AccreditationReportPolicy::update()
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Archivo PDF de reemplazo (opcional — solo si se reemplaza el PDF actual)
            'archivo' => [
                'sometimes',
                'file',
                'mimes:pdf',
                'max:51200',
            ],

            // Observaciones
            'observaciones' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Validaciones adicionales de coherencia de fechas.
     */
    public function withValidator(Validator $validator): void
    {
        // No hay validaciones adicionales por ahora
    }

    /**
     * Mensajes de error en español.
     */
    public function messages(): array
    {
        return [
            'archivo.file'  => 'El campo archivo debe ser un archivo válido.',
            'archivo.mimes' => 'El informe de acreditación debe ser un archivo PDF.',
            'archivo.max'   => 'El archivo no puede superar los 50 MB.',
            'observaciones.max' => 'Las observaciones no pueden superar los 500 caracteres.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
