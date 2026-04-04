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
        if ($this->user() === null) {
            return false;
        }

        // Modelo tradicional: autorizar según política de evidencia
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
            // Tipo de evidencia: archivo físico o enlace/URL
            'tipo' => [
                'required',
                'in:archivo,enlace',
            ],
            
            // Archivos físicos (solo requerido si tipo=archivo)
            'archivos' => [
                'required_if:tipo,archivo',
                'array',
                'min:1',
                'max:5', // Máximo 5 archivos
            ],
            'archivos.*' => [
                'file',
                'max:51200', // 50MB
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,bmp,svg,webp,mp4,avi,mov,wmv,mkv,webm,zip,rar,7z,txt,csv,rtf',
            ],
            
            // URLs/enlaces (solo requerido si tipo=enlace)
            'enlaces' => [
                'required_if:tipo,enlace',
                'array',
                'min:1',
                'max:5', // Máximo 5 enlaces
            ],
            'enlaces.*' => [
                'url',
                'max:2048', // Longitud máxima de URL
            ],
            'enlaces_nombres.*' => [
                'nullable',
                'string',
                'max:255',
            ],
            
            // Modelo tradicional: evidencia_id requerido
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
            'tipo.required' => 'Debe especificar el tipo de evidencia (archivo o enlace).',
            'tipo.in' => 'El tipo debe ser "archivo" o "enlace".',
            
            // Archivos
            'archivos.required_if' => 'Debe seleccionar al menos un archivo para subir.',
            'archivos.array' => 'Los archivos deben estar en formato de array.',
            'archivos.min' => 'Debe seleccionar al menos 1 archivo.',
            'archivos.max' => 'Puede subir un máximo de 5 archivos por solicitud.',
            'archivos.*.file' => 'Uno o más archivos proporcionados no son válidos.',
            'archivos.*.max' => 'Uno o más archivos no deben superar los 50MB.',
            'archivos.*.mimes' => 'Uno o más archivos tienen un formato no permitido.',
            
            // Enlaces
            'enlaces.required_if' => 'Debe proporcionar al menos un enlace/URL.',
            'enlaces.array' => 'Los enlaces deben estar en formato de array.',
            'enlaces.min' => 'Debe proporcionar al menos 1 enlace.',
            'enlaces.max' => 'Puede agregar un máximo de 5 enlaces por solicitud.',
            'enlaces.*.url' => 'Uno o más enlaces no tienen un formato de URL válido.',
            'enlaces.*.max' => 'Uno o más enlaces son demasiado largos (máx. 2048 caracteres).',
            'enlaces_nombres.*.max' => 'Uno o más nombres de enlace son demasiado largos (máx. 255 caracteres).',
            
            // Común
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
            'archivo'      => 'archivo',
            'evidencia_id' => 'evidencia',
            'proceso_id'   => 'proceso',
        ];
    }
}
