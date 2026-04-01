<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Request de validación para crear una solicitud de ampliación (modelo flexible - elemento).
 */
class StoreElementExtensionTimeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'elemento_asignacion_id' => [
                'required',
                'integer',
                'exists:ELEMENTO_ASIGNACION,elemento_asignacion_id',
                function ($attribute, $value, $fail) use ($userId) {
                    $existePendiente = DB::table('SOLICITUD_AMPLIACION_ELEMENTO')
                        ->where('usuario_id', $userId)
                        ->where('elemento_asignacion_id', $value)
                        ->where('estado', 'pendiente')
                        ->exists();
                    if ($existePendiente) {
                        $fail('Ya tiene una solicitud de ampliación pendiente para esta asignación de elemento.');
                    }
                },
            ],

            'motivo' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],

            'fecha_sugerida' => 'required|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'elemento_asignacion_id.required' => 'Debe especificar la asignación de elemento.',
            'elemento_asignacion_id.exists'   => 'La asignación de elemento no existe.',
            'motivo.required'                 => 'El motivo de la solicitud es obligatorio.',
            'motivo.min'                      => 'El motivo debe tener al menos 10 caracteres.',
            'motivo.max'                      => 'El motivo no puede exceder 1000 caracteres.',
            'fecha_sugerida.required'         => 'La fecha sugerida es obligatoria.',
            'fecha_sugerida.date'             => 'La fecha sugerida debe ser una fecha válida.',
            'fecha_sugerida.after'            => 'La fecha sugerida debe ser posterior a hoy.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('motivo')) {
            $this->merge(['motivo' => strip_tags($this->motivo)]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Error de validación.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
