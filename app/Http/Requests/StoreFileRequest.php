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
        // TODO: Habilitar cuando se implemente autenticación (HU-001)
        // return Gate::allows('upload', [
        //     \App\Models\File::class,
        //     $this->input('evidencia_id')
        // ]);
        
        // Por ahora permitir para pruebas
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
            'archivo' => [
                'required',
                'file',
                'max:51200', // 50MB en kilobytes
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp,mp4,avi,mov,wmv,mkv,webm,zip,rar,7z',
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
            // Para pruebas: permitir especificar usuario_id manualmente
            // TODO: Remover cuando se implemente autenticación (HU-001)
            'usuario_id' => [
                'required',
                'integer',
                'exists:USUARIO,usuario_id',
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
            'archivo.required' => 'Debe seleccionar un archivo para subir.',
            'archivo.file' => 'El archivo proporcionado no es válido.',
            'archivo.max' => 'El archivo no debe superar los 50MB.',
            'archivo.mimes' => 'El formato del archivo no está permitido. Formatos válidos: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, imágenes, videos, archivos comprimidos.',
            
            'evidencia_id.required' => 'Debe especificar la evidencia asociada.',
            'evidencia_id.integer' => 'El ID de evidencia debe ser un número entero.',
            'evidencia_id.exists' => 'La evidencia especificada no existe.',
            
            'proceso_id.required' => 'Debe especificar el proceso asociado.',
            'proceso_id.integer' => 'El ID de proceso debe ser un número entero.',
            'proceso_id.exists' => 'El proceso especificado no existe.',
            
            'usuario_id.required' => 'Debe especificar el usuario (para pruebas).',
            'usuario_id.integer' => 'El ID de usuario debe ser un número entero.',
            'usuario_id.exists' => 'El usuario especificado no existe.',
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
