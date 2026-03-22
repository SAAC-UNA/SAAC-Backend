<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\AccreditationCycle;

class AccreditationCycleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isPost   = $this->isMethod('POST');
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH']);
        $id = $this->route('accreditation_cycle') ?? $this->route('id');

        $rules = [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'carrera_sede_id' => [
                $isPost ? 'required' : 'sometimes',
                'integer',
                'exists:CARRERA_SEDE,carrera_sede_id',
            ],
            'nombre' => [
                $isPost ? 'required' : 'sometimes',
                'string',
                'max:50',
                // Unicidad por par (nombre + carrera_sede_id)
                Rule::unique((new AccreditationCycle)->getTable(), 'nombre')
                    ->where(fn($q) => $q->where(
                        'carrera_sede_id',
                        $this->input('carrera_sede_id')
                    ))
                    ->when($isUpdate && $id, fn($rule) => 
                        $rule->ignore($id, 'ciclo_acreditacion_id')
                    ),
            ],
            'modelo_estructura_id' => [
                $isPost ? 'required' : 'sometimes',
                'integer',
                'exists:MODELO_ESTRUCTURA,modelo_estructura_id',
            ],
            'estado' => [
                'sometimes',
                Rule::in([
                    AccreditationCycle::STATUS_ACTIVE,
                    AccreditationCycle::STATUS_INACTIVE,
                    AccreditationCycle::STATUS_COMPLETED,
                ]),
            ],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'carrera_sede_id.required' => 'La sede de carrera es obligatoria.',
            'carrera_sede_id.exists'   => 'La sede de carrera no existe.',
            'nombre.required'          => 'El nombre del ciclo es obligatorio.',
            'nombre.max'               => 'El nombre no puede superar los 250 caracteres.',
            'nombre.unique'            => 'Ya existe un ciclo con ese nombre en esta sede.',
            'modelo_estructura_id.required' => 'El modelo de estructura es obligatorio.',
            'modelo_estructura_id.exists'   => 'El modelo de estructura no existe.',
            'estado.in'                    => 'El estado debe ser activo, inactivo o completado.',
        ];
    }

    // Validar que en update venga al menos un campo
    public function withValidator($validator)
    {
        if (in_array($this->method(), ['PUT', 'PATCH'])) {
            $validator->after(function ($v) {
                if (!$this->hasAny(['carrera_sede_id', 'modelo_estructura_id', 'nombre', 'estado'])) {
                    $v->errors()->add('general', 'Debes enviar al menos un campo para actualizar.');
                }
            });
        }
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}