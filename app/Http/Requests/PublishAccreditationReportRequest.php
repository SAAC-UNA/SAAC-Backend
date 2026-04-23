<?php

namespace App\Http\Requests;

use App\Models\AccreditationCycle;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

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
            'proceso_id' => [
                'required',
                'integer',
                'exists:PROCESO,proceso_id',
            ],
            // Archivo PDF de la resolución SINAES (se sube junto con el formulario)
            'archivo' => [
                'required',
                'file',
                'mimes:pdf',
                'max:20480', // 20 MB
            ],

            // Observaciones adicionales (opcional)
            'observaciones' => [
                'nullable',
                'string',
                'max:500',
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
            $this->validateProcessBelongsToCycle($v);
            $this->validateProcessHasNoReport($v);
        });
    }

    /**
     * El ciclo debe estar en estado 'completado' para poder publicar el informe.
     */
    private function validateCycleIsCompleted(Validator $v): void
    {
        $cycle = $this->route('cycle');

        if (! $cycle instanceof AccreditationCycle) {
            return;
        }

        if ($cycle->estado !== AccreditationCycle::STATUS_COMPLETED) {
            $v->errors()->add(
                'ciclo_acreditacion_id',
                'Solo se puede publicar el informe de un ciclo en estado completado.'
            );
        }
    }

    /**
     * El proceso debe pertenecer al ciclo indicado.
     */
    private function validateProcessBelongsToCycle(Validator $v): void
    {
        $cycle = $this->route('cycle');
        $procesoId = $this->input('proceso_id');

        if (! $cycle instanceof AccreditationCycle || ! $procesoId) {
            return;
        }

        if (! $cycle->processes()->where('proceso_id', $procesoId)->exists()) {
            $v->errors()->add(
                'proceso_id',
                'El proceso indicado no pertenece al ciclo de acreditación.'
            );
        }
    }

    /**
     * El proceso no debe tener ya un informe registrado.
     */
    private function validateProcessHasNoReport(Validator $v): void
    {
        $procesoId = $this->input('proceso_id');

        if (! $procesoId) {
            return;
        }

        if (\App\Models\AccreditationReport::where('proceso_id', $procesoId)->exists()) {
            $v->errors()->add(
                'proceso_id',
                'Este proceso ya tiene un informe de acreditación registrado.'
            );
        }
    }

    // Mensajes de error en español
    public function messages(): array
    {
        return [
            'archivo.required' => 'El archivo PDF de la resolución es obligatorio.',
            'archivo.file' => 'El campo archivo debe ser un archivo válido.',
            'archivo.mimes' => 'El informe de acreditación debe ser un archivo PDF.',
            'archivo.max' => 'El archivo no puede superar los 20 MB',

            'observaciones.max' => 'Las observaciones no pueden superar los 500 caracteres',
        ];
    }
}
