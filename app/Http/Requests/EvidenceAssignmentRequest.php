<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EvidenceAssignmentRequest extends FormRequest
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
            'proceso_id' => 'required|integer|exists:PROCESO,proceso_id',
            'evidencia_id' => 'required|integer|exists:EVIDENCIA,evidencia_id',
            'usuarios' => 'sometimes|array|min:1',
            'usuarios.*' => 'integer|exists:USUARIO,usuario_id',
            'roles' => 'sometimes|array|min:1', 
            'roles.*' => 'integer|exists:roles,id',
            'fecha_limite' => 'nullable|date|after:now',
            'comentario' => 'nullable|string|max:500',
            
            // Al menos uno de usuarios o roles debe estar presente
            '_validate_assignment' => [
                'required_without_all:usuarios,roles',
                function ($attribute, $value, $fail) {
                    if (empty($this->usuarios) && empty($this->roles)) {
                        $fail('Debe asignar la evidencia a al menos un usuario o rol.');
                    }
                },
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
            'proceso_id.required' => 'El ID del proceso es requerido.',
            'proceso_id.exists' => 'El proceso especificado no existe.',
            'evidencia_id.required' => 'El ID de la evidencia es requerido.',
            'evidencia_id.exists' => 'La evidencia especificada no existe.',
            'usuarios.*.exists' => 'Uno o más usuarios especificados no existen.',
            'roles.*.exists' => 'Uno o más roles especificados no existen.',
            'fecha_limite.after' => 'La fecha límite debe ser posterior a la fecha actual.',
            'fecha_limite.date' => 'La fecha límite debe ser una fecha válida.',
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
                'message' => 'Error de validación.',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
