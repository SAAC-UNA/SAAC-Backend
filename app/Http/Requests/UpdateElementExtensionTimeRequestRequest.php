<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request de validación para actualizar una solicitud de ampliación (modelo flexible - elemento).
 * No permite cambiar el elemento_asignacion_id una vez creada la solicitud.
 */
class UpdateElementExtensionTimeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => [
                'sometimes',
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'fecha_sugerida' => [
                'sometimes',
                'required',
                'date',
                'after:today',
            ],
            // No se permite cambiar la asignación
            'elemento_asignacion_id'  => 'prohibited',
            'evidencia_asignacion_id' => 'prohibited',
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required'                    => 'El motivo de la solicitud es obligatorio.',
            'motivo.min'                         => 'El motivo debe tener al menos 10 caracteres.',
            'motivo.max'                         => 'El motivo no puede exceder 1000 caracteres.',
            'fecha_sugerida.date'                => 'La fecha sugerida debe ser una fecha válida.',
            'fecha_sugerida.after'               => 'La fecha sugerida debe ser posterior a hoy.',
            'elemento_asignacion_id.prohibited'  => 'No se puede cambiar la asignación de elemento en una solicitud existente.',
            'evidencia_asignacion_id.prohibited' => 'No se puede cambiar la asignación en una solicitud existente.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('motivo')) {
            $this->merge(['motivo' => strip_tags($this->motivo)]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Error de validación.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
