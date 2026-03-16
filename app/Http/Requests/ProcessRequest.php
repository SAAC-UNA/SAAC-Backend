<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'ciclo_acreditacion_id' => $isUpdate ? 'sometimes|exists:CICLO_ACREDITACION,ciclo_acreditacion_id' : 'required|exists:CICLO_ACREDITACION,ciclo_acreditacion_id',
            'tipo_proceso' => $isUpdate ? 'sometimes|string|max:50' : 'required|string|max:50',
            'modelo_estructura_id' => $isUpdate ? 'sometimes|exists:MODELO_ESTRUCTURA,modelo_estructura_id' : 'required|exists:MODELO_ESTRUCTURA,modelo_estructura_id',
            'activo' => 'boolean',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ciclo_acreditacion_id.required' => 'El ciclo de acreditación es obligatorio.',
            'ciclo_acreditacion_id.exists' => 'El ciclo de acreditación seleccionado no es válido.',
            'tipo_proceso.required' => 'El tipo de proceso es obligatorio.',
            'tipo_proceso.max' => 'El tipo de proceso no puede exceder 50 caracteres.',
            'modelo_estructura_id.required' => 'El modelo de estructura es obligatorio.',
            'modelo_estructura_id.exists' => 'El modelo de estructura seleccionado no es válido.',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
