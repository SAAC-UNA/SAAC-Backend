<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule; // NUEVO

class ComponentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isUpdate = in_array($this->method(), ['PUT','PATCH']);
        $idParam  = $this->route('componente') ?? $this->route('id'); // NUEVO

        $rules = [
            'dimension_id'  => [$isUpdate ? 'sometimes' : 'required','integer','exists:DIMENSION,dimension_id'],
            'comentario_id' => ['nullable','integer','exists:COMENTARIO,comentario_id'],
            'nombre'        => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:80',
                'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/',
            ],
            'nomenclatura'  => [$isUpdate ? 'sometimes' : 'required','string','max:20'],
        ];

        // Si van a cambiar el nombre en UPDATE, exigimos dimension_id para poder validar unicidad
        if ($isUpdate) { // NUEVO
            $rules['dimension_id'][] = 'required_with:nombre'; // NUEVO
        }

        // Unicidad: nombre único dentro de la misma dimensión (create/update)
        $uniqueNombre = Rule::unique('COMPONENTE', 'nombre') // NUEVO
            ->where(fn($q) => $q->where('dimension_id', $this->input('dimension_id'))); // NUEVO
        if ($isUpdate && $idParam) { // NUEVO
            $uniqueNombre = $uniqueNombre->ignore($idParam, 'componente_id'); // NUEVO
        }
        $rules['nombre'][] = $uniqueNombre; // NUEVO

        return $rules;
    }

    public function messages(): array
    {
        return [
            'dimension_id.required'  => 'La dimensión es obligatoria.',
            'dimension_id.exists'    => 'La dimensión no existe.',
            'dimension_id.required_with' => 'Debes enviar la dimensión cuando actualizas el nombre.', // NUEVO
            'comentario_id.exists'   => 'El comentario no existe.',
            'nombre.required'        => 'El nombre es obligatorio.',
            'nombre.regex'           => 'El nombre solo puede contener letras y espacios.',
            'nombre.unique'          => 'Ya existe un componente con ese nombre en esta dimensión.', // NUEVO
            'nomenclatura.required'  => 'La nomenclatura es obligatoria.',
        ];
    }

    // SIN CAMBIOS
    public function withValidator($validator)
    {
        if (in_array($this->method(), ['PUT','PATCH'])) {
            $validator->after(function ($v) {
                if (!$this->hasAny(['dimension_id','comentario_id','nombre','nomenclatura'])) {
                    $v->errors()->add('general', 'Debes enviar al menos un campo para actualizar.');
                }
            });
        }
    }

    // SIN CAMBIOS
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Datos inválidos.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
