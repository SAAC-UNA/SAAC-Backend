<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Se puede mejorar con roles (solo SuperUsuario)
        return true;
    }

    public function rules(): array
    {
        return [
            'ciclo_acreditacion_id' => 'required|exists:CICLO_ACREDITACION,ciclo_acreditacion_id',
            'nombre' => 'required|string|max:50',
            'activo' => 'boolean'
        ];
    }
}
