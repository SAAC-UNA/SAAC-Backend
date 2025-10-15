<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Criterion;

class CriterionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $table = (new Criterion)->getTable(); // 'CRITERIO'
        $isUpdate = in_array($this->method(), ['PUT','PATCH']);
        $id = $this->route('criterio') ?? $this->route('id');

        // Para la regla unique por (nomenclatura, componente_id) necesitamos conocer componente_id “efectivo”
        $currentCompId = null;
        if ($id) {
            // Obtener componente actual solo si es update y no viene en el body
            $current = Criterion::find($id);
            $currentCompId = $current?->componente_id;
        }
        $compId = $this->input('componente_id', $currentCompId);

        $rules = [
            'componente_id' => [$isUpdate ? 'sometimes' : 'required','integer','exists:COMPONENTE,componente_id'],
            'comentario_id' => ['nullable','integer','exists:COMENTARIO,comentario_id'],
            'descripcion'   => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:300',
                'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ .,\-:;]+$/',
            ],
            'nomenclatura'  => [$isUpdate ? 'sometimes' : 'required','string','max:20'],
        ];

        // unique(nomenclatura) dentro del mismo componente
        if ($compId !== null) {
            $unique = Rule::unique($table, 'nomenclatura')
                ->where(fn($q) => $q->where('componente_id', $compId));

            if ($id) {
                $pk = 'criterio_id';
                $unique = $unique->ignore($id, $pk);
            }

            $rules['nomenclatura'][] = $unique;
        }

        return $rules;
    }

    public function messages(): array
    {
        // mismos mensajes que tu controller
        return [
            'componente_id.required' => 'El componente es obligatorio.',
            'componente_id.exists'   => 'El componente no existe.',
            'comentario_id.exists'   => 'El comentario no existe.',
            'descripcion.required'   => 'La descripción es obligatoria.',
            'descripcion.regex'      => 'La descripción solo puede contener letras, espacios, puntos, comas, guiones, dos puntos y punto y coma.',
            'nomenclatura.required'  => 'La nomenclatura es obligatoria.',
            'nomenclatura.unique'    => 'Ya existe un criterio con esa nomenclatura en este componente.',
        ];
    }

    // Reproduce tu “al menos un campo” en update
    public function withValidator($validator)
    {
        if (in_array($this->method(), ['PUT','PATCH'])) {
            $validator->after(function ($v) {
                if (!$this->hasAny(['componente_id','comentario_id','descripcion','nomenclatura'])) {
                    $v->errors()->add('general', 'Debes enviar al menos un campo para actualizar.');
                }
            });
        }
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
