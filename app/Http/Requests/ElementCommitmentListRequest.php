<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para listar Compromisos de Mejora (modelo flexible) con paginación y filtros.
 * Equivalente a ImprovementCommitmentListRequest.
 */
class ElementCommitmentListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'         => 'integer|min:1',
            'per_page'     => 'nullable|integer|min:1|max:50',
            'search'       => 'string|nullable',
            'estado'       => 'string|nullable|in:Pendiente,En Progreso,Completado,Vencido',
            'proceso_id'   => 'integer|nullable|exists:PROCESO,proceso_id',
            'elemento_id'  => 'integer|nullable|exists:ELEMENTO,elemento_id',
            'usuario_id'   => 'integer|nullable|exists:USUARIO,usuario_id',
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer'      => 'El número de página debe ser un entero.',
            'page.min'          => 'El número de página debe ser al menos 1.',
            'per_page.integer'  => 'La cantidad de registros por página debe ser un entero.',
            'per_page.min'      => 'Debe solicitar al menos 1 registro por página.',
            'per_page.max'      => 'No puede solicitar más de 50 registros por página.',
            'estado.in'         => 'El estado debe ser uno de: Pendiente, En Progreso, Completado, Vencido.',
            'proceso_id.exists' => 'El proceso especificado no existe.',
            'elemento_id.exists'=> 'El elemento especificado no existe.',
            'usuario_id.exists' => 'El usuario especificado no existe.',
        ];
    }
}
