<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\AccreditationCycle;
use App\Models\File;

/**
 * Request para publicar el informe de acreditación de un ciclo.
 *
 * HU-028: Publicación de informe de acreditación aprobado.
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
            // ID del archivo PDF ya subido al sistema (debe existir en ARCHIVO)
            'archivo_id' => [
                'required',
                'integer',
                Rule::exists('ARCHIVO', 'archivo_id'),
            ],

            // Número de resolución oficial de SINAES (único en todo el sistema)
            'numero_resolucion' => [
                'required',
                'string',
                'max:100',
                Rule::unique('INFORME_ACREDITACION', 'numero_resolucion'),
            ],

            // Fecha en que SINAES emitió la resolución
            'fecha_resolucion' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'before_or_equal:today',
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
                'max:1000',
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
            $this->validateFileIsPdf($v);
            $this->validateVigenciaCoherence($v);
            $this->validateFilebelongsToCycle($v);
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
     * El archivo adjunto debe ser un PDF.
     * Se valida tanto el MIME type como la extensión del nombre original.
     * El sistema acepta otros formatos para evidencias, pero la resolución
     * oficial de SINAES solo se acepta en PDF.
     */
    private function validateFileIsPdf(Validator $v): void
    {
        $archivoId = $this->input('archivo_id');

        if (!$archivoId) {
            return; // La regla 'required' ya lo captura
        }

        $file = File::find($archivoId);

        if (!$file) {
            return; // La regla 'exists' ya lo captura
        }

        // Verificar MIME type
        $mimeInvalido = $file->tipo_mime !== 'application/pdf';

        // Verificar extensión del nombre original (defensa en profundidad)
        $extension = strtolower(pathinfo($file->nombre_original ?? '', PATHINFO_EXTENSION));
        $extensionInvalida = $extension !== 'pdf';

        if ($mimeInvalido || $extensionInvalida) {
            $v->errors()->add(
                'archivo_id',
                'El informe de acreditación debe ser un archivo PDF.'
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

    /**
     * El archivo debe pertenecer al mismo ciclo de acreditación.
     * Previene que un usuario malicioso referencie un archivo de otra carrera.
     * La cadena de verificación: ARCHIVO → proceso_id → PROCESO → ciclo_acreditacion_id.
     */
    private function validateFilebelongsToCycle(Validator $v): void
    {
        $archivoId = $this->input('archivo_id');
        $cycle     = $this->route('cycle');

        if (!$archivoId || !$cycle instanceof AccreditationCycle) {
            return;
        }

        $file = File::with('process')->find($archivoId);

        if (!$file || !$file->proceso_id || !$file->process) {
            return; // Archivo sin proceso accesible (caso borde): se permite, se delega a la Policy
        }

        if ((int) $file->process->ciclo_acreditacion_id !== (int) $cycle->ciclo_acreditacion_id) {
            $v->errors()->add(
                'archivo_id',
                'El archivo no pertenece a este ciclo de acreditación.'
            );
        }
    }

    // Mensajes de error en español
    public function messages(): array
    {
        return [
            'archivo_id.required'              => 'El archivo PDF de la resolución es obligatorio.',
            'archivo_id.integer'               => 'El identificador del archivo no es válido.',
            'archivo_id.exists'                => 'El archivo indicado no existe en el sistema.',

            'numero_resolucion.required'       => 'El número de resolución es obligatorio.',
            'numero_resolucion.max'            => 'El número de resolución no puede superar los 100 caracteres.',
            'numero_resolucion.unique'         => 'Este número de resolución ya está registrado en otro informe.',

            'fecha_resolucion.required'        => 'La fecha de resolución es obligatoria.',
            'fecha_resolucion.date'            => 'La fecha de resolución no tiene un formato válido.',
            'fecha_resolucion.date_format'     => 'La fecha de resolución debe tener el formato AAAA-MM-DD.',
            'fecha_resolucion.before_or_equal' => 'La fecha de resolución no puede ser una fecha futura.',

            'vigencia_desde.required'          => 'La fecha de inicio de vigencia es obligatoria.',
            'vigencia_desde.date'              => 'La fecha de inicio de vigencia no tiene un formato válido.',
            'vigencia_desde.date_format'       => 'La fecha de inicio de vigencia debe tener el formato AAAA-MM-DD.',

            'vigencia_hasta.required'          => 'La fecha de fin de vigencia es obligatoria.',
            'vigencia_hasta.date'              => 'La fecha de fin de vigencia no tiene un formato válido.',
            'vigencia_hasta.date_format'       => 'La fecha de fin de vigencia debe tener el formato AAAA-MM-DD.',
            'vigencia_hasta.after'             => 'La fecha de fin de vigencia debe ser posterior a la fecha de inicio.',

            'observaciones.max'                => 'Las observaciones no pueden superar los 1000 caracteres.',
        ];
    }
}
