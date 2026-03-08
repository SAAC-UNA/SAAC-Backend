<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request para validar la aprobación o rechazo de una solicitud de ampliación.
 * 
 * Valida que:
 * - El estado sea 'aprobada' o 'rechazada'
 * - La justificación sea clara cuando se rechaza
 */
class ReviewExtensionRequestRequest extends FormRequest
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
            // Estado: opcional porque la acción ya está implícita en la ruta (/aprobar vs /rechazar)
            'estado' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['aprobada', 'rechazada'])
            ],
            
            // Justificación: máximo 500 caracteres
            'justificacion' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
                'min:10',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.,;:\-_()¿?¡!\[\]\/]+$/'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'Debe especificar el estado de la solicitud.',
            'estado.in' => 'El estado debe ser "aprobada" o "rechazada".',
            
            'justificacion.required_if' => 'Debe proporcionar una justificación al rechazar la solicitud.',
            'justificacion.min' => 'La justificación debe tener al menos 10 caracteres.',
            'justificacion.max' => 'La justificación no puede exceder los 500 caracteres.',
            'justificacion.regex' => 'La justificación contiene caracteres no permitidos.',
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
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Errores de validación',
            'errors' => $validator->errors()
        ], 422));
    }
}
