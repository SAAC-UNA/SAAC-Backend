<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * NOTA: Este FormRequest NO SE USA actualmente
 * 
 * Los modelos de estructura están predefinidos en la migración 041:
 * - Modelo 1: SINAES 2018 (tradicional)
 * - Modelo 2: SINAES 2026 (jerarquia_flexible)
 * 
 * No se permite crear ni editar modelos vía API.
 * Solo operaciones permitidas: listar, ver, toggle activo
 * 
 * Este archivo se conserva por si en el futuro se necesita habilitar
 * la creación/edición de modelos personalizados.
 */
class ModeloEstructuraRequest extends FormRequest
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
            'nombre' => $isUpdate ? 'sometimes|string|max:100' : 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'tipo' => $isUpdate ? 'sometimes|string|max:30|in:tradicional,jerarquia_flexible' : 'required|string|max:30|in:tradicional,jerarquia_flexible',
            'version' => 'nullable|string|max:20',
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
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder 100 caracteres.',
            'tipo.required' => 'El tipo de modelo es obligatorio.',
            'tipo.in' => 'El tipo debe ser: tradicional o jerarquia_flexible.',
            'tipo.max' => 'El tipo no puede exceder 30 caracteres.',
            'version.max' => 'La versión no puede exceder 20 caracteres.',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
