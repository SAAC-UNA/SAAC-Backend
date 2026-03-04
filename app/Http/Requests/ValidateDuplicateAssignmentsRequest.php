<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateDuplicateAssignmentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
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
            'evidencia_id' => ['required', 'integer', 'exists:EVIDENCIA,evidencia_id'],
            'usuarios' => ['required', 'array', 'min:1'],
            'usuarios.*' => ['required', 'integer', 'exists:USUARIO,usuario_id'],
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
            'proceso_id.required' => 'El campo proceso id es requerido.',
            'proceso_id.exists' => 'El campo proceso id seleccionado no existe.',
            'evidencia_id.required' => 'El campo evidencia id es requerido.',
            'evidencia_id.exists' => 'El campo evidencia id seleccionado no existe.',
            'usuarios.required' => 'El campo usuarios es requerido.',
            'usuarios.array' => 'El campo usuarios debe ser un arreglo.',
            'usuarios.min' => 'El campo usuarios debe contener al menos :min elementos.',
            'usuarios.*.required' => 'Cada usuario es requerido.',
            'usuarios.*.integer' => 'Cada usuario debe ser un número entero.',
            'usuarios.*.exists' => 'El campo usuarios.:position seleccionado no existe.',
        ];
    }
}
