<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignCareersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'careers' => ['required', 'array', 'min:1'],
            'careers.*' => ['integer', 'distinct', 'exists:CARRERA_SEDE,carrera_sede_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'careers.required' => 'Debe enviar al menos una carrera.',
            'careers.array' => 'El formato de carreras no es válido.',
            'careers.min' => 'Debe enviar al menos una carrera.',
            'careers.*.integer' => 'Cada carrera debe ser un número válido.',
            'careers.*.distinct' => 'No se pueden repetir carreras.',
            'careers.*.exists' => 'Una o más sedes de carrera no existen en el sistema.',,
        ];
    }
}
