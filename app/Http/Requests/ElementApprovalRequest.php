<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ElementApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proceso_id'         => ['required', 'integer', 'exists:PROCESO,proceso_id'],
            'comentario'         => ['nullable', 'string', 'max:100'],
            'fecha_limite'       => ['nullable', 'date', 'after:now'],
            'nueva_fecha_limite' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'proceso_id.required' => 'El ID del proceso es requerido.',
            'proceso_id.integer'  => 'El ID del proceso debe ser un número entero.',
            'proceso_id.exists'   => 'El proceso especificado no existe.',
            'comentario.string'   => 'El comentario debe ser una cadena de texto.',
            'comentario.max'      => 'El comentario no puede exceder 100 caracteres.',
            'fecha_limite.date'   => 'La fecha límite debe ser una fecha válida.',
            'fecha_limite.after'  => 'La nueva fecha límite debe ser posterior a la fecha actual.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Errores de validación.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
