<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ImprovementCommitmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Limitado por políticas o middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proceso_id' => [
                'required',
                'integer',
                'exists:PROCESO,proceso_id',
                function ($attribute, $value, $fail) {
                    $proceso = DB::table('PROCESO')->where('proceso_id', $value)->first();
                    if (!$proceso) return;
                    if ($proceso->tipo_proceso !== 'Compromiso de mejora') {
                        $fail('El proceso debe ser de tipo "Compromiso de mejora".');
                    }
                    if (!$proceso->activo) {
                        $fail('El proceso debe estar activo.');
                    }
                },
            ],
            'selecciones' => [
                'required',
                'array',
                'min:1',
            ],
            'selecciones.*.entidad_tipo' => [
                'required',
                'string',
                Rule::in(['ESTANDAR', 'DIMENSION', 'COMPONENTE', 'CRITERIO', 'EVIDENCIA']),
            ],
            'selecciones.*.entidad_id' => [
                'required',
                'integer',
            ],
            'descripcion' => [
                'required',
                'string',
                'max:100',
            ],
            'fecha_inicio' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'fecha_fin' => [
                'required',
                'date',
                'after:fecha_inicio',
            ],
            'estado' => [
                'sometimes',
                'string',
                Rule::in(['Pendiente', 'En Progreso', 'Completado', 'Vencido']),
            ],
            'evidencias_asignadas' => [
                'nullable',
                'array',
            ],
            'evidencias_asignadas.*' => [
                'integer',
                'exists:EVIDENCIA_ASIGNACION,evidencia_asignacion_id',
            ],
            'evidencias_asignar' => [
                'nullable',
                'array',
            ],
            'evidencias_asignar.*.evidencia_id' => [
                'required',
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

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proceso_id.required' => 'El proceso es obligatorio.',
            'proceso_id.exists' => 'El proceso no existe.',
            'entidad_tipo.required' => 'El tipo de entidad es obligatorio.',
            'entidad_tipo.in' => 'El tipo de entidad debe ser ESTANDAR, DIMENSION, COMPONENTE, CRITERIO o EVIDENCIA.',
            'entidad_id.required' => 'El ID de la entidad es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.max' => 'La descripción no puede exceder 100 caracteres.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_inicio.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy.',
            'fecha_fin.required' => 'La fecha fin es obligatoria.',
            'fecha_fin.date' => 'La fecha fin debe ser una fecha válida.',
            'fecha_fin.after' => 'La fecha fin debe ser posterior a la fecha de inicio.',
            'estado.in' => 'El estado debe ser Pendiente, En Progreso, Completado o Vencido.',
            'evidencias_asignadas.array' => 'Las evidencias asignadas deben ser un arreglo.',
            'evidencias_asignadas.*.exists' => 'Una o más evidencias asignadas no existen.',
            'evidencias_asignar.array' => 'Las evidencias a asignar deben ser un arreglo.',
            'evidencias_asignar.*.evidencia_id.required' => 'El ID de evidencia es obligatorio.',
            'evidencias_asignar.*.evidencia_id.exists' => 'Una o más evidencias no existen.',
            'evidencias_asignar.*.usuarios.array' => 'Los usuarios deben ser un arreglo.',
            'evidencias_asignar.*.usuarios.*.exists' => 'Uno o más usuarios no existen.',
            'evidencias_asignar.*.roles.array' => 'Los roles deben ser un arreglo.',
            'evidencias_asignar.*.roles.*.exists' => 'Uno o más roles no existen.',
        ];
    }
}

