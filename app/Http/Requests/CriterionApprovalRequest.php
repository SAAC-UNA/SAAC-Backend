<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CriterionApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proceso_id' => ['required', 'integer', 'exists:PROCESO,proceso_id'],
            'comentario' => ['nullable', 'string', 'max:100']
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
            'proceso_id.integer' => 'El ID del proceso debe ser un número entero.',
            'proceso_id.exists' => 'El proceso especificado no existe.',
            'comentario.string' => 'El comentario debe ser una cadena de texto.',
            'comentario.max' => 'El comentario no puede exceder 100 caracteres.'
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
            'message' => 'Errores de validación.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
