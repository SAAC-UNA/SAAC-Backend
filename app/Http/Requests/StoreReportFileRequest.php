<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('archivos.upload');
    }

    public function rules(): array
    {
        return [
            'archivos' => ['required', 'array', 'min:1', 'max:5'],
            'archivos.*' => [
                'file',
                'max:51200',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,bmp,svg,webp,avif,heic,heif,mp4,avi,mov,wmv,mkv,webm,zip,rar,7z,txt,csv,rtf',
            ],
            'proceso_id' => ['required', 'integer', 'exists:PROCESO,proceso_id'],
            'tipo' => ['required', 'string', 'in:Informes Universitarios,Informes SINAES,Resoluciones SINAES,Certificaciones'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivos.required' => 'Debe seleccionar al menos un archivo para subir.',
            'archivos.max' => 'Puede subir un máximo de 5 archivos por solicitud.',
            'archivos.*.max' => 'Uno o más archivos no deben superar los 50MB.',
            'archivos.*.mimes' => 'Uno o más archivos tienen un formato no permitido.',
            'proceso_id.required' => 'Debe especificar el proceso asociado.',
            'proceso_id.exists' => 'El proceso especificado no existe.',
            'tipo.required' => 'Debe especificar la categoría del informe.',
            'tipo.in' => 'La categoría del informe no es válida.',
        ];
    }
}
