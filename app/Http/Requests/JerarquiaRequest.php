<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JerarquiaRequest extends FormRequest
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
        $jerarquiaId = $this->route('id');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'modelo_estructura_id' => $isUpdate ? 'sometimes|exists:MODELO_ESTRUCTURA,modelo_estructura_id' : 'required|exists:MODELO_ESTRUCTURA,modelo_estructura_id',
            'parent_id' => [
                'nullable',
                'exists:JERARQUIA,jerarquia_id',
                // Evitar que un elemento sea su propio padre en UPDATE
                $isUpdate ? Rule::notIn([$jerarquiaId]) : '',
            ],
            'nombre' => $isUpdate ? 'sometimes|required|string|max:100' : 'required|string|max:100',
            'tipo' => $isUpdate ? 'sometimes|required|string|max:30' : 'required|string|max:30',
            'categoria' => 'nullable|in:A,B,C,D',
            'nomenclatura' => 'nullable|string|max:20',
            'descripcion' => 'nullable|string',
            'orden' => 'nullable|integer|min:0',
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
            'modelo_estructura_id.required' => 'El modelo de estructura es obligatorio.',
            'modelo_estructura_id.exists' => 'El modelo de estructura seleccionado no es válido.',
            'parent_id.exists' => 'El elemento padre seleccionado no existe.',
            'parent_id.not_in' => 'Un elemento no puede ser su propio padre.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder 100 caracteres.',
            'tipo.required' => 'El tipo es obligatorio.',
            'tipo.max' => 'El tipo no puede exceder 30 caracteres.',
            'categoria.in' => 'La categoría debe ser A, B, C o D.',
            'nomenclatura.max' => 'La nomenclatura no puede exceder 20 caracteres.',
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden debe ser mayor o igual a 0.',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
