<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request de validación para actualizar una solicitud de ampliación del profesor (RF-15).
 * No permite cambiar la evidencia_asignacion_id.
 */
class UpdateExtensionTimeRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se manejará con policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Motivo: opcional en update, pero si se envía debe cumplir reglas
            'motivo' => [
                'sometimes',
                'required',
                'string',
                'min:10',
                'max:1000'
            ],
            
            // Fecha sugerida: opcional en update, pero si se envía debe ser futura
            'fecha_sugerida' => [
                'sometimes',
                'required',
                'date',
                'after:today'
            ],

            // CRÍTICO: NO permitir cambiar el ID de asignación una vez creada la solicitud
            'evidencia_asignacion_id' => 'prohibited',
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
            'motivo.required' => 'El motivo de la solicitud es obligatorio.',
            'motivo.string' => 'El motivo debe ser texto.',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres para ser claro.',
            'motivo.max' => 'El motivo no puede exceder 1000 caracteres.',
            
            'fecha_sugerida.required' => 'La fecha sugerida para la nueva fecha límite es obligatoria.',
            'fecha_sugerida.date' => 'La fecha sugerida debe ser una fecha válida.',
            'fecha_sugerida.after' => 'La fecha sugerida debe ser posterior a hoy.',
            
            'evidencia_asignacion_id.prohibited' => 'No se puede cambiar la evidencia asignada en una solicitud existente.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'motivo' => 'motivo de la solicitud',
            'fecha_sugerida' => 'fecha sugerida',
            'evidencia_asignacion_id' => 'evidencia asignada',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Error de validación en los datos enviados.',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
