<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use App\Models\Evidence;
use Illuminate\Validation\Rule;

class EvidenceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $table    = (new Evidence)->getTable(); // 'EVIDENCIA'
        $isUpdate = in_array($this->method(), ['PUT','PATCH']);
        $idParam  = $this->route('evidencia') ?? $this->route('id');

        // Obtener el criterio “efectivo” (si no viene en update, usar el actual)
        $current = $idParam ? Evidence::find($idParam) : null;
        $criterioId = $this->input('criterio_id', $current?->criterio_id);

        // Componente al que pertenece ese criterio (para la unicidad por componente)
        $componentId = null;
        if ($criterioId) {
            $componentId = DB::table('CRITERIO')
                ->where('criterio_id', $criterioId)
                ->value('componente_id');
        } elseif ($current?->criterio_id) {
            $componentId = DB::table('CRITERIO')
                ->where('criterio_id', $current->criterio_id)
                ->value('componente_id');
        }

        $rules = [
            'criterio_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:CRITERIO,criterio_id',
            ],
            'estado'      => [$isUpdate ? 'sometimes' : 'required', 'string', Rule::in(Evidence::ESTADOS)],
            'descripcion' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:80',
                'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ .,\-:;]+$/',
            ],
            'nomenclatura' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:20'],
        ];

        // Unicidad de "descripcion" (nombre) dentro del componente
        if (!is_null($componentId)) {
            $uniqueNombre = Rule::unique($table, 'descripcion')
                ->where(function ($q) use ($componentId) {
                    // Todos los criterios que pertenecen a ese componente
                    $q->whereIn('criterio_id', function ($sub) use ($componentId) {
                        $sub->select('criterio_id')
                            ->from('CRITERIO')
                            ->where('componente_id', $componentId);
                    });
                });

            if ($idParam) {
                $uniqueNombre = $uniqueNombre->ignore($idParam, 'evidencia_id');
            }

            $rules['descripcion'][] = $uniqueNombre;
        }

        // Unicidad de nomenclatura dentro del mismo criterio (modelo tradicional)
        if (!is_null($criterioId)) {
            $uniqueNomen = Rule::unique($table, 'nomenclatura')
                ->where(fn ($q) => $q->where('criterio_id', $criterioId));

            if ($idParam) {
                $uniqueNomen = $uniqueNomen->ignore($idParam, 'evidencia_id');
            }

            $rules['nomenclatura'][] = $uniqueNomen;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'criterio_id.exists'    => 'El criterio seleccionado no existe.',
            'criterio_id.required'  => 'El criterio es obligatorio.',
            'estado.required'       => 'El estado es obligatorio.',
            'estado.in'             => 'El estado indicado no es válido.',
            'descripcion.required'  => 'La descripción es obligatoria.',
            'descripcion.regex'     => 'La descripción solo puede contener letras, espacios, puntos, comas, guiones, dos puntos y punto y coma.',
            'descripcion.unique'    => 'Ya existe una evidencia con ese nombre en este componente.',
            'nomenclatura.required' => 'La nomenclatura es obligatoria.',
            'nomenclatura.unique'   => 'Ya existe una evidencia con esa nomenclatura en este criterio/elemento.',
        ];
    }

    public function withValidator($validator)
    {
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH']);

        $validator->after(function ($v) use ($isUpdate) {
            if ($isUpdate && !$this->hasAny(['criterio_id', 'estado', 'descripcion', 'nomenclatura'])) {
                $v->errors()->add('general', 'Debes enviar al menos un campo para actualizar.');
            }
        });
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
