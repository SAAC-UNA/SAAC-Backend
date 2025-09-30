<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EvidenceStateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isUpdate = in_array($this->method(), ['PUT','PATCH']);
        $id = $this->route('estado_evidencium') ?? $this->route('id'); // por si tu parámetro es diferente

        return [
            'nombre' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:100',
                'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/',
                // unique: tabla, columna, ignorar_id, columna_pk
                'unique:ESTADO_EVIDENCIA,nombre' . ($isUpdate && $id ? ",$id,estado_evidencia_id" : '')
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Debes enviar el campo nombre.',
            'nombre.regex'    => 'El nombre solo puede contener letras y espacios.',
            'nombre.unique'   => 'Ya existe un estado de evidencia con ese nombre.',
            'nombre.max'      => 'El nombre no puede superar los 100 caracteres.',
            'nombre.string'   => 'El nombre debe ser una cadena de texto.',
        ];
    }

    public function withValidator($validator)
    {
        // Reproduce tu: "Debes enviar el campo nombre." en update si no viene
        if (in_array($this->method(), ['PUT','PATCH'])) {
            $validator->after(function ($v) {
                if (!$this->has('nombre')) {
                    $v->errors()->add('nombre', 'Debes enviar el campo nombre.');
                }
            });
        }
    }

    protected function failedValidation(Validator $validator)
    {
        // mismo formato de error controller original
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
