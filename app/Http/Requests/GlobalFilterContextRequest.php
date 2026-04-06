<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlobalFilterContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'career_campus_id'      => ['nullable', 'integer', 'exists:CARRERA_SEDE,carrera_sede_id'],
            'ciclo_acreditacion_id' => ['nullable', 'integer', 'exists:CICLO_ACREDITACION,ciclo_acreditacion_id'],
            'proceso_id'            => ['nullable', 'integer', 'exists:PROCESO,proceso_id'],
        ];
    }
}
