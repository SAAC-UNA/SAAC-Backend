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

        // También necesitamos el elemento_id efectivo para unicidad de nomenclatura
        // en evidencias flexibles (paralelo a criterio_id en el modelo tradicional).
        $elementoId = $this->input('elemento_id', $current?->elemento_id);

        $rules = [
            // ── HU-012 (escritura flexible) — Gap 2 ──────────────────────────────────
            // ANTES: criterio_id era 'required' siempre → el POST de una evidencia
            //        flexible (con elemento_id) devolvía 422 "El criterio es obligatorio".
            //
            // DESPUÉS: ambos son nullable individualmente. La regla XOR (exactamente
            //          uno de los dos debe venir en CREATE) se valida en withValidator().
            //          En UPDATE se mantiene 'sometimes' para no obligar a re-enviar el ancla.
            // ─────────────────────────────────────────────────────────────────────────
            'criterio_id' => [
                $isUpdate ? 'sometimes' : 'nullable',
                'integer',
                'exists:CRITERIO,criterio_id',
            ],
            'elemento_id' => [
                $isUpdate ? 'sometimes' : 'nullable',
                'integer',
                'exists:ELEMENTO,elemento_id',
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

        // HU-012 (escritura flexible): unicidad de nomenclatura dentro del mismo elemento
        // (paralelo exacto a la unicidad por criterio del modelo tradicional).
        if (!is_null($elementoId)) {
            $uniqueNomenFlexible = Rule::unique($table, 'nomenclatura')
                ->where(fn ($q) => $q->where('elemento_id', $elementoId));

            if ($idParam) {
                $uniqueNomenFlexible = $uniqueNomenFlexible->ignore($idParam, 'evidencia_id');
            }

            $rules['nomenclatura'][] = $uniqueNomenFlexible;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'criterio_id.exists'    => 'El criterio seleccionado no existe.',
            'elemento_id.exists'    => 'El elemento seleccionado no existe.',
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
            $tieneCriterio = !is_null($this->input('criterio_id'));
            $tieneElemento = !is_null($this->input('elemento_id'));

            if (!$isUpdate) {
                // HU-012 (escritura flexible) — regla XOR en CREATE:
                // Debe venir criterio_id (mod. tradicional) O elemento_id (mod. flexible),
                // pero nunca los dos a la vez ni ninguno de los dos.
                if ($tieneCriterio && $tieneElemento) {
                    $v->errors()->add('criterio_id', 'Una evidencia no puede pertenecer a un criterio y a un elemento al mismo tiempo.');
                    $v->errors()->add('elemento_id', 'Una evidencia no puede pertenecer a un criterio y a un elemento al mismo tiempo.');
                }
                if (!$tieneCriterio && !$tieneElemento) {
                    $v->errors()->add('criterio_id', 'Debes indicar criterio_id (modelo tradicional) o elemento_id (modelo flexible).');
                }
            }

            if ($isUpdate) {
                // En UPDATE: detectar si el usuario intenta cambiar el "ancla" de tipo
                // (de criterio → elemento o viceversa), lo que rompería la consistencia.
                if ($tieneCriterio && $tieneElemento) {
                    $v->errors()->add('criterio_id', 'No puedes cambiar una evidencia de criterio a elemento al mismo tiempo.');
                }

                if (!$this->hasAny(['criterio_id', 'elemento_id', 'estado', 'descripcion', 'nomenclatura'])) {
                    $v->errors()->add('general', 'Debes enviar al menos un campo para actualizar.');
                }
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
