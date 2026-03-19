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
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH']);
        $id = $this->route('accreditation_cycle') ?? $this->route('id');

        $rules = [
            'carrera_sede_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:CARRERA_SEDE,carrera_sede_id',
            ],
            'nombre' => [
                $isUpdate ? 'sometimes' : 'required',
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
            'estado.in'                => 'El estado debe ser activo, inactivo o completado.',
        ];
    }

    // Validar que en update venga al menos un campo
    public function withValidator($validator)
    {
        if (in_array($this->method(), ['PUT', 'PATCH'])) {
            $validator->after(function ($v) {
                if (!$this->hasAny(['carrera_sede_id', 'nombre', 'estado'])) {
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