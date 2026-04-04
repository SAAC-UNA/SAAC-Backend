<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request exclusiva para subida de archivos en el Modelo Flexible (HU-008).
 *
 * Diferencias con StoreFileRequest (modelo tradicional):
 * - Solo acepta elemento_id (sin evidencia_id ni criterio_id).
 * - Sin lógica de Gate por evidencia: la autorización es por permiso global.
 */
class StoreElementFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:archivo,enlace'],

            'archivos' => ['required_if:tipo,archivo', 'array', 'min:1', 'max:5'],
            'archivos.*' => [
                'file',
                'max:51200', // 50 MB
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,bmp,svg,webp,mp4,avi,mov,wmv,mkv,webm,zip,rar,7z,txt,csv,rtf',
            ],

            'enlaces'          => ['required_if:tipo,enlace', 'array', 'min:1', 'max:5'],
            'enlaces.*'        => ['url', 'max:2048'],
            'enlaces_nombres'  => ['sometimes', 'array'],
            'enlaces_nombres.*' => ['nullable', 'string', 'max:255'],

            'elemento_id' => ['required', 'integer', 'exists:ELEMENTO,elemento_id'],
            'proceso_id'  => ['required', 'integer', 'exists:PROCESO,proceso_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required'            => 'Debe especificar el tipo (archivo o enlace).',
            'tipo.in'                  => 'El tipo debe ser "archivo" o "enlace".',
            'archivos.required_if'     => 'Debe seleccionar al menos un archivo.',
            'archivos.max'             => 'Puede subir máximo 5 archivos por solicitud.',
            'archivos.*.max'           => 'Cada archivo no debe superar los 50 MB.',
            'archivos.*.mimes'         => 'Uno o más archivos tienen formato no permitido.',
            'enlaces.required_if'      => 'Debe proporcionar al menos un enlace.',
            'enlaces.max'              => 'Puede agregar máximo 5 enlaces por solicitud.',
            'enlaces.*.url'            => 'Uno o más enlaces no son URLs válidas.',
            'elemento_id.required'     => 'Debe especificar el elemento asociado.',
            'elemento_id.exists'       => 'El elemento especificado no existe.',
            'proceso_id.required'      => 'Debe especificar el proceso asociado.',
            'proceso_id.exists'        => 'El proceso especificado no existe.',
        ];
    }
}
