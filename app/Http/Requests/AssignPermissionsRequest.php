<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Sprint 3 se protegerá con can:admin.super
    }

    public function rules(): array
    {
        return [
            'modules' => ['required','array','min:1'],
            'modules.*' => ['array','min:1'],      // cada módulo => array de acciones
            'modules.*.*' => ['string'],           // cada acción => string
        ];
    }

    public function messages(): array
    {
        return [
            'modules.required' => 'Debe enviar al menos un módulo.',
            'modules.array'    => 'El formato de módulos no es válido.',
            'modules.min'      => 'Debe enviar al menos un módulo.',
        ];
    }
}
