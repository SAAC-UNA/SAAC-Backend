<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'ciclo_acreditacion_id' => [
                'required_without:proceso_id',
                'integer',
                'exists:CICLO_ACREDITACION,ciclo_acreditacion_id',
            ],
            'proceso_id' => [
                'required_without:ciclo_acreditacion_id',
                'integer',
                'exists:PROCESO,proceso_id',
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
            'evidencias_asignar.*.usuario_id' => [
                'required',
                'integer',
                'exists:USUARIO,usuario_id',
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
            'ciclo_acreditacion_id.required_without' => 'El ciclo de acreditación es obligatorio si no se proporciona el proceso.',
            'ciclo_acreditacion_id.exists' => 'El ciclo de acreditación no existe.',
            'proceso_id.required_without' => 'El proceso es obligatorio si no se proporciona el ciclo de acreditación.',
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
            'evidencias_asignar.*.usuario_id.required' => 'El ID de usuario es obligatorio.',
            'evidencias_asignar.*.usuario_id.exists' => 'Uno o más usuarios no existen.',
        ];
    }
}

