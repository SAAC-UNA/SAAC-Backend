<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\ImprovementCommitment;

class UpdateImprovementCommitmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización fina se maneja por middleware/policies si aplica
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // En UPDATE todo es opcional (soporta PATCH-like por PUT)
            'proceso_id' => [
                'sometimes',
                'integer',
                'exists:PROCESO,proceso_id',
                function ($attribute, $value, $fail) {
                    $tipo = DB::table('PROCESO')->where('proceso_id', $value)->value('tipo_proceso');
                    if ($tipo !== 'Compromiso de mejora') {
                        $fail('El proceso debe ser de tipo "Compromiso de mejora".');
                    }
                },
            ],
            'selecciones' => [
                'sometimes',
                'array',
                'min:1',
            ],
            'selecciones.*.entidad_tipo' => [
                'required_with:selecciones',
                'string',
                Rule::in(['ESTANDAR', 'DIMENSION', 'COMPONENTE', 'CRITERIO', 'EVIDENCIA']),
            ],
            'selecciones.*.entidad_id' => [
                'required_with:selecciones',
                'integer',
            ],
            'descripcion' => [
                'sometimes',
                'string',
                'max:100',
            ],
            // En UPDATE no forzamos after_or_equal:today (puede haber compromisos ya iniciados)
            'fecha_inicio' => [
                'sometimes',
                'date',
            ],
            'fecha_fin' => [
                'sometimes',
                'date',
            ],
            'estado' => [
                'sometimes',
                'string',
                Rule::in(['Pendiente', 'En Progreso', 'Completado', 'Vencido']),
            ],
            'evidencias_asignar' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'evidencias_asignar.*.evidencia_id' => [
                'required_with:evidencias_asignar',
                'integer',
                'exists:EVIDENCIA,evidencia_id',
            ],
            'evidencias_asignar.*.usuarios' => [
                'nullable',
                'array',
            ],
            'evidencias_asignar.*.usuarios.*' => [
                'integer',
                'exists:USUARIO,usuario_id',
            ],
            'evidencias_asignar.*.roles' => [
                'nullable',
                'array',
            ],
            'evidencias_asignar.*.roles.*' => [
                'integer',
                'exists:roles,id',
            ],
            'evidencias_asignar.*.fecha_asignacion' => [
                'nullable',
                'date',
            ],
            'evidencias_asignar.*.fecha_limite' => [
                'nullable',
                'date',
            ],
            'evidencias_asignar.*.comentario' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $id = $this->route('id');
            if (!$id) {
                return;
            }

            $commitment = ImprovementCommitment::query()->find($id);
            if (!$commitment) {
                return; // el controller ya responde 404
            }

            // Regla de seguridad: por defecto NO permitimos cambiar el proceso en UPDATE.
            // Si el cliente lo envía, debe coincidir con el actual.
            if ($this->has('proceso_id') && (int)$this->input('proceso_id') !== (int)$commitment->proceso_id) {
                $validator->errors()->add('proceso_id', 'El proceso no puede modificarse en la actualización de un compromiso de mejora.');
            }

            // Regla de negocio: fecha_inicio NO se modifica (se define al crear).
            if ($this->has('fecha_inicio')) {
                $currentFechaInicio = $commitment->fecha_inicio?->format('Y-m-d');
                if ($currentFechaInicio !== null && (string)$this->input('fecha_inicio') !== $currentFechaInicio) {
                    $validator->errors()->add('fecha_inicio', 'La fecha de inicio no puede modificarse en la actualización de un compromiso de mejora.');
                }
            }
        });
    }
}
