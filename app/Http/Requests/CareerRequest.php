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
        $id = $this->route('career') ?? $this->route('id');

        return [
            'nombre' => [
                'required',
                'string',
                'max:250',
                'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/',
                Rule::unique('CARRERA', 'nombre')
                    ->where(fn ($query) => $query->where('universidad_id', $this->universidad_id))
                    ->ignore($id, 'carrera_id'),
            ],
            'universidad_id' => [
                'required',
                'integer',
                'exists:UNIVERSIDAD,universidad_id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'         => 'El nombre es obligatorio.',
            'nombre.regex'            => 'El nombre solo puede contener letras y espacios.',
            'nombre.unique'           => 'Ya existe una carrera con ese nombre.',
            'universidad_id.required' => 'Debe seleccionar una universidad.',
            'universidad_id.exists'   => 'La universidad seleccionada no existe.',
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
