<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

/**
 * Validación para actualizar un Compromiso de Mejora (modelo flexible).
 * Equivalente a UpdateImprovementCommitmentRequest.
 * Todos los campos son opcionales (soporta PUT parcial).
 */
class UpdateElementCommitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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

            // Cambiar el elemento raíz del compromiso (re-calcula descend. automáticamente)
            'elemento_id' => [
                'sometimes',
                'integer',
                'exists:ELEMENTO,elemento_id',
            ],

            'descripcion' => [
                'sometimes',
                'string',
                'max:500',
            ],

            // En UPDATE no forzamos after_or_equal:today
            'fecha_inicio' => [
                'sometimes',
                'date',
            ],

            'fecha_fin' => [
                'sometimes',
                'date',
                'after:fecha_inicio',
            ],

            'estado' => [
                'sometimes',
                'string',
                Rule::in(['Pendiente', 'En Progreso', 'Completado', 'Vencido']),
            ],

            // Reemplazar asignaciones del compromiso (crea nuevas o reutiliza existentes)
            'elementos_asignar' => [
                'sometimes',
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
            'elementos_asignar.*.roles' => [
                'nullable',
                'array',
            ],
            'elementos_asignar.*.roles.*' => [
                'integer',
                'exists:roles,id',
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
            'proceso_id.exists'     => 'El proceso especificado no existe.',
            'elemento_id.exists'    => 'El elemento especificado no existe.',
            'descripcion.max'       => 'La descripción no puede exceder 500 caracteres.',
            'fecha_fin.after'       => 'La fecha fin debe ser posterior a la fecha de inicio.',
            'estado.in'                               => 'El estado debe ser Pendiente, En Progreso, Completado o Vencido.',
            'elementos_asignar.*.elemento_id.required' => 'Cada asignación debe indicar el elemento_id.',
            'elementos_asignar.*.elemento_id.exists'   => 'Uno o más elementos no existen.',
            'elementos_asignar.*.usuarios.*.exists'    => 'Uno o más usuarios no existen.',
            'elementos_asignar.*.roles.array'           => 'Los roles deben ser un arreglo.',
            'elementos_asignar.*.roles.*.exists'        => 'Uno o más roles no existen.',
            'elementos_asignar.*.fecha_limite.date'    => 'La fecha límite debe ser una fecha válida.',
        ];
    }
}
