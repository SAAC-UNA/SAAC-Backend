<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Dimension;

class DimensionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $table = (new Dimension)->getTable(); // 'DIMENSION'
        $id = $this->route('dimension') ?? $this->route('id');

        $rules = [
            'comentario_id' => ['required','integer','exists:COMENTARIO,comentario_id'],
            'nombre'        => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/',
            ],
            'nomenclatura'  => ['required','string','max:20'],
        ];

        // unique en create/update (ignora el actual si hay id)
        $unique = Rule::unique($table, 'nomenclatura');
        if ($id) {
            $pk = 'dimension_id';
            $unique = $unique->ignore($id, $pk);
        }
        $rules['nomenclatura'][] = $unique;

        return $rules;
    }

    public function messages(): array
    {
        return [
            'comentario_id.required' => 'El comentario es obligatorio.',
            'comentario_id.exists'   => 'El comentario no existe.',
            'nombre.required'        => 'El nombre es obligatorio.',
            'nombre.regex'           => 'El nombre solo puede contener letras y espacios.',
            'nomenclatura.required'  => 'La nomenclatura es obligatoria.',
            'nomenclatura.unique'    => $this->route('dimension') || $this->route('id')
                                        ? 'Ya existe otra dimensión con esa nomenclatura.'
                                        : 'Ya existe una dimensión con esa nomenclatura.',
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
