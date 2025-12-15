<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request para validar la creación de una solicitud de ampliación.
 * 
 * Valida que:
 * - La asignación de evidencia exista
 * - El motivo sea claro y no vacío
 * - La fecha sugerida sea futura
 */
class StoreExtensionRequestRequest extends FormRequest
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
            // La asignación de evidencia debe existir
            'evidencia_asignacion_id' => 'required|integer|exists:EVIDENCIA_ASIGNACION,evidencia_asignacion_id',
            
            // Motivo: obligatorio, máximo 300 caracteres, sin caracteres raros
            'motivo' => [
                'required',
                'string',
                'max:300',
                'min:10',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.,;:\-_()¿?¡!\[\]\/]+$/'
            ],
            
            // Fecha sugerida: obligatoria, debe ser futura antes tenia now, 
            // pero esta es para la hora exacta, mejor tenerlo como today es mas intuitivo
            // y con today no implica hora, solo fecha
            'fecha_sugerida' => 'required|date|after:today',
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
            'evidencia_asignacion_id.required' => 'Debe especificar la asignación de evidencia.',
            'evidencia_asignacion_id.exists' => 'La asignación de evidencia no existe.',
            
            'motivo.required' => 'El motivo de la solicitud es obligatorio.',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres.',
            'motivo.max' => 'El motivo no puede exceder los 300 caracteres.',
            'motivo.regex' => 'El motivo contiene caracteres no permitidos.',
            
            'fecha_sugerida.required' => 'Debe especificar una fecha sugerida.',
            'fecha_sugerida.date' => 'La fecha sugerida debe ser una fecha válida.',
            'fecha_sugerida.after' => 'La fecha sugerida debe ser posterior a hoy.',
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
