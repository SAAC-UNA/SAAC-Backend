<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\AccreditationCycle;

/**
 * Request para publicar el informe de acreditación de un ciclo.
 *
 * HU-027: Publicación de informe de acreditación aprobado.
 *
 * Valida que:
 * - El ciclo exista y esté en estado completado (proceso de evaluación cerrado por el Administrador).
 * - El archivo sea un PDF ya subido al sistema (registro en ARCHIVO).
 * - Los datos de resolución sean coherentes (fechas, unicidad del número).
 * - La vigencia sea un rango válido (vigencia_desde >= fecha_resolucion, vigencia_hasta > vigencia_desde).
 * - El archivo pertenece al mismo ciclo (seguridad: evita referencias cruzadas entre carreras).
 */
class PublishAccreditationReportRequest extends FormRequest
{
    // La autorización se delega a AccreditationReportPolicy::publish()
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Archivo PDF de la resolución SINAES (se sube junto con el formulario)
            'archivo' => [
                'required',
                'file',
                'mimes:pdf',
                'max:20480', // 20 MB
            ],

            // Número de resolución oficial de SINAES (único en todo el sistema)
            'numero_resolucion' => [
                'required',
                'string',
                'max:100',
                Rule::unique('INFORME_ACREDITACION', 'numero_resolucion'),
            ],

            // Inicio de la vigencia de la acreditación
            'vigencia_desde' => [
                'required',
                'date',
                'date_format:Y-m-d',
            ],

            // Fin de la vigencia de la acreditación (debe ser posterior a vigencia_desde)
            'vigencia_hasta' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after:vigencia_desde',
            ],

            // Observaciones adicionales (opcional)
            'observaciones' => [
                'nullable',
                'string',
                'max:500',
            ],

            // Indica si la resolución acredita o no la carrera
            'esta_acreditada' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Validaciones adicionales que requieren lógica de negocio
     * y no se pueden expresar con reglas declarativas.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $this->validateCycleIsCompleted($v);
            $this->validateCycleHasNoReport($v);
            $this->validateVigenciaCoherence($v);
        });
    }

    /**
     * El ciclo debe estar en estado 'completado' para poder publicar el informe.
     * El estado 'completado' es asignado manualmente por el Administrador cuando
     * decide que el proceso de evaluación ha concluido. Pueden existir criterios
     * con compromisos de mejora; SINAES puede igualmente emitir la resolución.
     */
    private function validateCycleIsCompleted(Validator $v): void
    {
        // El ciclo llega como parámetro de ruta: /api/cycles/{cycle}/report
        $cycle = $this->route('cycle');

        if (!$cycle instanceof AccreditationCycle) {
            return; // El binding falla antes si no existe, no es necesario validar aquí
        }

        if ($cycle->estado !== AccreditationCycle::STATUS_COMPLETED) {
            $v->errors()->add(
                'ciclo_acreditacion_id',
                'Solo se puede publicar el informe de un ciclo en estado completado.'
            );
        }
    }

    /**
     * El ciclo no debe tener ya un informe registrado (publicado o despublicado).
     * Cada ciclo admite exactamente un informe.
     */
    private function validateCycleHasNoReport(Validator $v): void
    {
        $cycle = $this->route('cycle');

        if (!$cycle instanceof AccreditationCycle) {
            return;
        }

        if ($cycle->accreditationReport()->exists()) {
            $v->errors()->add(
                'ciclo_acreditacion_id',
                'Este ciclo ya tiene un informe de acreditación registrado.'
            );
        }
    }

    /**
     * La vigencia_desde no puede ser anterior a la fecha_resolucion.
     * SINAES no puede otorgar vigencia retroactiva previa a la resolución.
     */
    private function validateVigenciaCoherence(Validator $v): void
    {
        $fechaResolucion = $this->input('fecha_resolucion');
        $vigenciaDesde   = $this->input('vigencia_desde');

        if (!$fechaResolucion || !$vigenciaDesde) {
            return; // Las reglas 'required' ya lo capturan
        }

        if ($vigenciaDesde < $fechaResolucion) {
            $v->errors()->add(
                'vigencia_desde',
                'La vigencia no puede iniciar antes de la fecha de resolución de SINAES.'
            );
        }
    }

    // Mensajes de error en español
    public function messages(): array
    {
        return [
            'archivo.required'             => 'El archivo PDF de la resolución es obligatorio.',
            'archivo.file'                 => 'El campo archivo debe ser un archivo válido.',
            'archivo.mimes'                => 'El informe de acreditación debe ser un archivo PDF.',
            'archivo.max'                  => 'El archivo no puede superar los 20 MB',

            'numero_resolucion.required'   => 'El número de resolución es obligatorio.',
            'numero_resolucion.max'        => 'El número de resolución no puede superar los 100 caracteres.',
            'numero_resolucion.unique'     => 'Este número de resolución ya está registrado en otro informe.',

            'vigencia_desde.required'     => 'La fecha de inicio de vigencia es obligatoria.',
            'vigencia_desde.date'         => 'La fecha de inicio de vigencia no tiene un formato válido.',
            'vigencia_desde.date_format'  => 'La fecha de inicio de vigencia debe tener el formato AAAA-MM-DD.',

            'vigencia_hasta.required'     => 'La fecha de fin de vigencia es obligatoria.',
            'vigencia_hasta.date'         => 'La fecha de fin de vigencia no tiene un formato válido.',
            'vigencia_hasta.date_format'  => 'La fecha de fin de vigencia debe tener el formato AAAA-MM-DD.',
            'vigencia_hasta.after'        => 'La fecha de fin de vigencia debe ser posterior a la fecha de inicio.',

            'observaciones.max'           => 'Las observaciones no pueden superar los 500 caracteres',
        ];
    }
}
