<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para listar compromisos de mejora con paginación y filtros.
 */
class ImprovementCommitmentListRequest extends FormRequest
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
            'page' => 'integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'search' => 'string|nullable',
            'estado' => 'string|nullable|in:Pendiente,En Progreso,Completado,Vencido',
            'proceso_id' => 'integer|nullable|exists:PROCESO,proceso_id',
            'usuario_id' => 'integer|nullable|exists:USUARIO,usuario_id',
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
            'page.integer' => 'El número de página debe ser un entero.',
            'page.min' => 'El número de página debe ser al menos 1.',
            'per_page.integer' => 'La cantidad de registros por página debe ser un entero.',
            'per_page.min' => 'Debe solicitar al menos 1 registro por página.',
            'per_page.max' => 'No puede solicitar más de 50 registros por página.',
            'estado.in' => 'El estado debe ser uno de: Pendiente, En Progreso, Completado, Vencido.',
            'proceso_id.exists' => 'El proceso especificado no existe.',
            'usuario_id.exists' => 'El usuario especificado no existe.',
        ];
    }
}
