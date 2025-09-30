<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'         => [
                'required',
                'string',
                'max:250',
                'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/',
            ],
            'universidad_id' => ['required','integer','exists:UNIVERSIDAD,universidad_id'],
            'sede_id'        => ['required','integer','exists:SEDE,sede_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'         => 'El nombre es obligatorio.',
            'nombre.regex'            => 'El nombre solo puede contener letras y espacios.',
            'universidad_id.required' => 'La universidad es obligatoria.',
            'universidad_id.exists'   => 'La universidad no existe.',
            'sede_id.required'        => 'La sede es obligatoria.',
            'sede_id.exists'          => 'La sede no existe.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Datos inválidos.',
                'errors'  => $validator->errors()
            ], 422)
        );
    }
}
