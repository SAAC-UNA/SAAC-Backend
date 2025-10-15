<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule; 

class CareerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:250',
                'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/',
                Rule::unique('CARRERA','nombre')
                    ->where(fn($q) => $q->where('facultad_id', $this->input('facultad_id')))
            ],
            'facultad_id' => ['required','integer','exists:FACULTAD,facultad_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'      => 'El nombre es obligatorio.',
            'nombre.regex'         => 'El nombre solo puede contener letras y espacios.',
            'nombre.unique'        => 'Ya existe una carrera con ese nombre en la misma facultad.',
            'facultad_id.required' => 'La facultad es obligatoria.',
            'facultad_id.exists'   => 'La facultad no existe.',
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
