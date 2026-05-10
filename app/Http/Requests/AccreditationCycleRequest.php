<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\AccreditationCycle;

/**
 * Request unificado para Ciclos de Acreditación.
 * Maneja validaciones de GET, POST, PUT y PATCH en una sola clase.
 */
class AccreditationCycleRequest extends FormRequest
{
    // Todo usuario autenticado que llegue aquí ya pasó el middleware de permisos
    public function authorize(): bool { return true; }

    /**
     * Reglas de validación por campo.
     * Los campos marcados 'required' solo aplican en POST;
     * en PUT/PATCH son 'sometimes' (opcionales, PATCH-friendly).
     */
    public function rules(): array
    {
        $isPost   = $this->isMethod('POST');
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH']);
        // El ID del ciclo viene como parámetro de ruta en update
        $id = $this->route('accreditation_cycle') ?? $this->route('id');

        $rules = [
            // Para paginación en GET index
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],

            // Obligatorio en creación; debe existir en CARRERA_SEDE
            'carrera_sede_id' => [
                $isPost ? 'required' : 'sometimes',
                'integer',
                'exists:CARRERA_SEDE,carrera_sede_id',
            ],

            // Nombre opcional: se autogenera en el servicio a partir de las fechas.
            'nombre' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'fecha_inicio' => [
                $isPost ? 'required' : 'sometimes',
                'string',
                'digits:4',
            ],

            'fecha_fin' => [
                $isPost ? 'required' : 'sometimes',
                'string',
                'digits:4',
                'gte:fecha_inicio',
            ],

            // Obligatorio en creación; debe existir en MODELO_ESTRUCTURA
            'modelo_estructura_id' => [
                $isPost ? 'required' : 'sometimes',
                'integer',
                'exists:MODELO_ESTRUCTURA,modelo_estructura_id',
            ],

            // Siempre opcional; solo acepta los tres estados definidos en el modelo
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

    // Mensajes de error en español para cada regla
    public function messages(): array
    {
        return [
            'carrera_sede_id.required' => 'La sede de carrera es obligatoria.',
            'carrera_sede_id.exists'   => 'La sede de carrera no existe.',
            'nombre.required'          => 'El nombre del ciclo es obligatorio.',
            'nombre.max'               => 'El nombre no puede superar los 50 caracteres.',
            'fecha_inicio.required'    => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date'        => 'La fecha de inicio no tiene un formato válido.',
            'fecha_fin.required'       => 'La fecha de fin es obligatoria.',
            'fecha_fin.date'           => 'La fecha de fin no tiene un formato válido.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser mayor o igual a la fecha de inicio.',
            'modelo_estructura_id.required' => 'El modelo de estructura es obligatorio.',
            'modelo_estructura_id.exists'   => 'El modelo de estructura no existe.',
            'estado.in'                    => 'El estado debe ser activo, inactivo o completado.',
        ];
    }

    /**
     * Validaciones adicionales que dependen del estado de la BD
     * (no se pueden expresar con reglas declarativas simples).
     */
    public function withValidator($validator)
    {
        // En update: exige que llegue al menos un campo para modificar
        if (in_array($this->method(), ['PUT', 'PATCH'])) {
            $validator->after(function ($v) {
                if (!$this->hasAny(['modelo_estructura_id', 'nombre', 'estado'])) {
                    if (!$this->hasAny(['fecha_inicio', 'fecha_fin'])) {
                    $v->errors()->add('general', 'Debes enviar al menos un campo para actualizar.');
                    }
                }
            });
        }

        // Reglas de coherencia del dominio
        $validator->after(function ($v) {
            $this->validateSingleActiveCycle($v);
            $this->validateDateOverlap($v);
        });
    }

    /**
     * AC-6: Máximo un ciclo activo por combinación carrera+sede.
     *
     * - POST: se evalúa con el estado enviado (default 'activo' si no viene).
     * - PATCH/PUT: solo se evalúa si el estado nuevo es explícitamente 'activo'.
     *   Se excluye el ciclo actual para que no se bloquee a sí mismo.
     */
    private function validateSingleActiveCycle($v): void
    {
        $isPost   = $this->isMethod('POST');
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH']);

        if ($isPost) {
            // En POST solo aplica AC-6 cuando el cliente envía estado activo explícito.
            $nuevoEstado = $this->input('estado');
            $carreraSede = $this->input('carrera_sede_id');
            $excluirId   = null;
        } elseif ($isUpdate) {
            // Solo interesa si se está cambiando el estado a 'activo'
            $nuevoEstado = $this->input('estado');
            $excluirId   = $this->route('accreditation_cycle') ?? $this->route('id');
            // Si el body no trae carrera_sede_id, se toma del ciclo actual en BD
            $cicloActual = $excluirId ? AccreditationCycle::find($excluirId) : null;
            $carreraSede = $this->input('carrera_sede_id', $cicloActual?->carrera_sede_id);
        } else {
            return;
        }

        // Solo aplica si el ciclo resultante quedaría en estado 'activo'
        if ($nuevoEstado !== AccreditationCycle::STATUS_ACTIVE) {
            return;
        }

        // Sin carrera_sede no podemos verificar; las reglas declarativas ya lo capturan
        if (!$carreraSede) {
            return;
        }

        // Busca si ya existe otro ciclo activo en la misma carrera+sede
        $query = AccreditationCycle::where('carrera_sede_id', $carreraSede)
            ->where('estado', AccreditationCycle::STATUS_ACTIVE);

        if ($excluirId) {
            // Excluye el ciclo que se está actualizando
            $query->where('ciclo_acreditacion_id', '!=', $excluirId);
        }

        if ($query->exists()) {
            $v->errors()->add(
                'carrera_sede_id',
                'Ya existe un ciclo activo para esta carrera en esta sede.'
            );
        }
    }

    /**
     * Evita solapamiento de periodos en una misma carrera+sede.
     */
    private function validateDateOverlap($v): void
    {
        $isPost   = $this->isMethod('POST');
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH']);

        if (!$isPost && !$isUpdate) {
            return;
        }

        $id = $this->route('accreditation_cycle') ?? $this->route('id');
        $currentCycle = $id ? AccreditationCycle::find($id) : null;

        $carreraSede = $this->input('carrera_sede_id', $currentCycle?->carrera_sede_id);
        $fechaInicio = $this->input('fecha_inicio', $currentCycle?->fecha_inicio);
        $fechaFin = $this->input('fecha_fin', $currentCycle?->fecha_fin);

        if (!$carreraSede || !$fechaInicio || !$fechaFin) {
            return;
        }

        $query = AccreditationCycle::query()
            ->where('carrera_sede_id', $carreraSede)
            ->whereNotNull('fecha_inicio')
            ->whereNotNull('fecha_fin')
            ->whereDate('fecha_inicio', '<=', $fechaFin)
            ->whereDate('fecha_fin', '>=', $fechaInicio);

        if ($id) {
            $query->where('ciclo_acreditacion_id', '!=', $id);
        }

        if ($query->exists()) {
            $v->errors()->add(
                'fecha_inicio',
                'Ya existe un ciclo para esta carrera-sede cuyo periodo se solapa con las fechas indicadas.'
            );
        }
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}