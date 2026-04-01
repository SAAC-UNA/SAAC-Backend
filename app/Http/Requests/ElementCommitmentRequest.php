<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

/**
 * Validación para crear un Compromiso de Mejora (modelo flexible).
 * Equivalente a ImprovementCommitmentRequest pero usando ELEMENTO/ELEMENTO_ASIGNACION.
 */
class ElementCommitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // proceso_id obligatorio (en el modelo flexible siempre se envía directamente)
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

            // Elemento raíz a vincular (la cascada resuelve el resto del árbol)
            'elemento_id' => [
                'required',
                'integer',
                'exists:ELEMENTO,elemento_id',
            ],

            'descripcion' => [
                'required',
                'string',
                'max:500',
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

            // Asignaciones a crear dentro del compromiso
            'elementos_asignar' => [
                'nullable',
                'array',
            ],
            'elementos_asignar.*.elemento_id' => [
                'required',
                'integer',
                'exists:ELEMENTO,elemento_id',
            ],
            'elementos_asignar.*.usuarios' => [
                'nullable',
                'array',
            ],
            'elementos_asignar.*.usuarios.*' => [
                'integer',
                'exists:USUARIO,usuario_id',
            ],
            'elementos_asignar.*.fecha_limite' => [
                'nullable',
                'date',
            ],
            'elementos_asignar.*.comentario' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'proceso_id.required'      => 'El proceso es obligatorio.',
            'proceso_id.exists'        => 'El proceso especificado no existe.',
            'elemento_id.required'     => 'El elemento raíz es obligatorio.',
            'elemento_id.exists'       => 'El elemento especificado no existe.',
            'descripcion.required'     => 'La descripción es obligatoria.',
            'descripcion.max'          => 'La descripción no puede exceder 500 caracteres.',
            'fecha_inicio.required'    => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date'        => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_inicio.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy.',
            'fecha_fin.required'       => 'La fecha fin es obligatoria.',
            'fecha_fin.date'           => 'La fecha fin debe ser una fecha válida.',
            'fecha_fin.after'          => 'La fecha fin debe ser posterior a la fecha de inicio.',
            'estado.in'                          => 'El estado debe ser Pendiente, En Progreso, Completado o Vencido.',
            'elementos_asignar.array'             => 'Los elementos a asignar deben ser un arreglo.',
            'elementos_asignar.*.elemento_id.required' => 'Cada asignación debe indicar el elemento_id.',
            'elementos_asignar.*.elemento_id.exists'   => 'Uno o más elementos no existen.',
            'elementos_asignar.*.usuarios.*.exists'    => 'Uno o más usuarios no existen.',
            'elementos_asignar.*.fecha_limite.date'    => 'La fecha límite debe ser una fecha válida.',
        ];
    }
}
