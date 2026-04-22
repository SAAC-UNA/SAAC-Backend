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
        $report = $this->route('report');
        $reportId = $report instanceof AccreditationReport
            ? $report->informe_acreditacion_id
            : null;

        return [
            // Archivo PDF de reemplazo (opcional — solo si se reemplaza el PDF actual)
            'archivo' => [
                'sometimes',
                'file',
                'mimes:pdf',
                'max:51200',
            ],

            // Número de resolución — único excepto para este mismo informe
            'numero_resolucion' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('INFORME_ACREDITACION', 'numero_resolucion')
                    ->ignore($reportId, 'informe_acreditacion_id'),
            ],

            // Fecha en que SINAES emitió la resolución
            'fecha_resolucion' => [
                'sometimes',
                'date',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            // Inicio de la vigencia
            'vigencia_desde' => [
                'sometimes',
                'date',
                'date_format:Y-m-d',
            ],

            // Fin de la vigencia
            'vigencia_hasta' => [
                'sometimes',
                'date',
                'date_format:Y-m-d',
                'after:vigencia_desde',
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
        $validator->after(function (Validator $validador) {
            $this->validateVigenciaCoherence($validador);
        });
    }

    /**
     * Si se envía vigencia_hasta, debe ser posterior a vigencia_desde
     * (tomando vigencia_desde del request o del informe existente).
     */
    private function validateVigenciaCoherence(Validator $validador): void
    {
        if ($validador->errors()->any()) {
            return;
        }

        $report = $this->route('report');
        $desde  = $this->input('vigencia_desde')
            ?? ($report instanceof AccreditationReport ? $report->vigencia_desde?->format('Y-m-d') : null);
        $hasta  = $this->input('vigencia_hasta')
            ?? ($report instanceof AccreditationReport ? $report->vigencia_hasta?->format('Y-m-d') : null);

        if ($desde && $hasta && $hasta <= $desde) {
            $validador->errors()->add('vigencia_hasta', 'La vigencia hasta debe ser posterior a la vigencia desde.');
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
