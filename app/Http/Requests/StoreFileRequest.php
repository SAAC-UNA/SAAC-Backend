<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * La autorización se verifica contra la FilePolicy.
     */
    public function authorize(): bool
    {
        // Si no hay usuario autenticado, denegar
        if (!auth()->check()) {
            return false;
        }
        
        // Autorizar si el usuario puede subir archivos a esta evidencia
        return Gate::allows('upload', [
            \App\Models\File::class,
            $this->input('evidencia_id')
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Soporte para subida múltiple (máximo 5 archivos)
            'archivos' => [
                'required',
                'array',
                'min:1', // Al menos 1 archivo
                'max:5', // Máximo 5 archivos
            ],
            'archivos.*' => [
                'file',
                'max:51200', // 50MB en kilobytes
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,bmp,svg,webp,mp4,avi,mov,wmv,mkv,webm,zip,rar,7z,txt,csv,rtf',
            ],
            'evidencia_id' => [
                'required',
                'integer',
                'exists:EVIDENCIA,evidencia_id',
            ],
            'proceso_id' => [
                'required',
                'integer',
                'exists:PROCESO,proceso_id',
            ],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivos.required' => 'Debe seleccionar al menos un archivo para subir.',
            'archivos.array' => 'Los archivos deben estar en formato de array.',
            'archivos.min' => 'Debe seleccionar al menos 1 archivo.',
            'archivos.max' => 'Puede subir un máximo de 5 archivos por solicitud.',
            'archivos.*.file' => 'Uno o más archivos proporcionados no son válidos.',
            'archivos.*.max' => 'Uno o más archivos no deben superar los 50MB.',
            'archivos.*.mimes' => 'Uno o más archivos tienen un formato no permitido. Formatos válidos: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, imágenes, videos, archivos comprimidos.',
            
            'evidencia_id.required' => 'Debe especificar la evidencia asociada.',
            'evidencia_id.integer' => 'El ID de evidencia debe ser un número entero.',
            'evidencia_id.exists' => 'La evidencia especificada no existe.',
            
            'proceso_id.required' => 'Debe especificar el proceso asociado.',
            'proceso_id.integer' => 'El ID de proceso debe ser un número entero.',
            'proceso_id.exists' => 'El proceso especificado no existe.',
        ];
    }

    /**
     * Nombres de atributos personalizados para mensajes de error.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'archivo' => 'archivo',
            'evidencia_id' => 'evidencia',
            'proceso_id' => 'proceso',
        ];
    }
}
