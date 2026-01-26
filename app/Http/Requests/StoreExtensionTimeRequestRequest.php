<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Request de validación para crear una solicitud de ampliación del profesor (RF-15).
 */
class StoreExtensionTimeRequestRequest extends FormRequest
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
     * ESTÁNDAR PL-10: Validación y sanitización de inputs
     * - Sanitización contra XSS (strip_tags, htmlspecialchars)
     * - Validación de tipos y longitudes
     * - Validación de relaciones (exists en BD)
     * - Validación de duplicados (no permitir múltiples solicitudes PENDIENTES para misma evidencia)
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    
    public function rules(): array
    {
       
        $userId = Auth::id();
        
        return [
            // La asignación de evidencia debe existir
            // VALIDACIÓN ADICIONAL: No permitir duplicados PENDIENTES para misma evidencia del mismo usuario
            'evidencia_asignacion_id' => [
                'required',
                'integer',
                'exists:EVIDENCIA_ASIGNACION,evidencia_asignacion_id',
                function ($attribute, $value, $fail) use ($userId) {
                    $existePendiente = DB::table('SOLICITUD_AMPLIACION')
                        ->where('usuario_id', $userId)
                        ->where('evidencia_asignacion_id', $value)
                        ->where('estado', 'Pendiente')
                        ->exists();
                    
                    if ($existePendiente) {
                        $fail('Ya tiene una solicitud de ampliación PENDIENTE para esta evidencia. Debe esperar su resolución antes de crear otra.');
                    }
                },
            ],
            
            // Motivo: obligatorio, entre 10 y 1000 caracteres
            // PL-10: Sanitización contra XSS
            'motivo' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            
            // Fecha sugerida: obligatoria, debe ser futura (posterior a hoy)
            'fecha_sugerida' => 'required|date|after:today',
        ];
    }
    
    /**
     * Prepare the data for validation.
     * 
     * ESTÁNDAR PL-10: Sanitización de inputs antes de validación
     * Limpia etiquetas HTML para prevenir XSS
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('motivo')) {
            $this->merge([
                'motivo' => strip_tags($this->motivo),
            ]);
        }
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
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres para una explicación adecuada.',
            'motivo.max' => 'El motivo no puede exceder los 1000 caracteres.',
            
            'fecha_sugerida.required' => 'Debe especificar una fecha sugerida para la nueva fecha límite.',
            'fecha_sugerida.date' => 'La fecha sugerida debe ser una fecha válida.',
            'fecha_sugerida.after' => 'La fecha sugerida debe ser posterior a hoy.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'evidencia_asignacion_id' => 'asignación de evidencia',
            'motivo' => 'motivo',
            'fecha_sugerida' => 'fecha sugerida',
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
            'message' => 'Los datos proporcionados no son válidos.',
            'errors' => $validator->errors()
        ], 422));
    }
}
