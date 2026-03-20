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
            'tipo_proceso' => $isUpdate
                ? 'sometimes|string|in:Autoevaluación,Compromiso de mejora'
                : 'required|string|in:Autoevaluación,Compromiso de mejora',
            'fecha_inicio'        => 'nullable|date',
            'fecha_finalizacion'  => 'nullable|date|after_or_equal:fecha_inicio',
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
            'ciclo_acreditacion_id.exists'   => 'El ciclo de acreditación seleccionado no es válido.',
            'tipo_proceso.required'          => 'El tipo de proceso es obligatorio.',
            'tipo_proceso.in'                => 'El tipo de proceso debe ser: Autoevaluación o Compromiso de mejora.',
            'fecha_finalizacion.after_or_equal' => 'La fecha de finalización debe ser igual o posterior a la fecha de inicio.',
            'activo.boolean'                 => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
