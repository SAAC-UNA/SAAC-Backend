<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccreditationCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'carrera_sede_id' => 'required|exists:CARRERA_SEDE,carrera_sede_id',
            'nombre' => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'carrera_sede_id.required' => 'Debe indicar la carrera y sede.',
            'carrera_sede_id.exists' => 'La carrera-sede seleccionada no existe en el sistema.',
            'nombre.required' => 'El nombre del ciclo es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
        ];
    }
}
