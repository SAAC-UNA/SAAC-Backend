<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request para crear solicitudes de ampliación en el modelo FLEXIBLE.
 *
 * Diferencia con StoreExtensionRequestRequest (tradicional):
 *   - Valida `elemento_asignacion_id` en lugar de `evidencia_asignacion_id`
 *   - Referencia la tabla ELEMENTO_ASIGNACION
 */
class StoreFlexibleExtensionRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'elemento_asignacion_id' => 'required|integer|exists:ELEMENTO_ASIGNACION,elemento_asignacion_id',

            'motivo' => [
                'required',
                'string',
                'max:300',
                'min:10',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.,;:\-_()\¿?¡!\[\]\/]+$/',
            ],

            'fecha_sugerida' => 'required|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'elemento_asignacion_id.required' => 'Debe especificar la asignación de elemento.',
            'elemento_asignacion_id.exists'   => 'La asignación de elemento no existe.',

            'motivo.required' => 'El motivo de la solicitud es obligatorio.',
            'motivo.min'      => 'El motivo debe tener al menos 10 caracteres.',
            'motivo.max'      => 'El motivo no puede exceder los 300 caracteres.',
            'motivo.regex'    => 'El motivo contiene caracteres no permitidos.',

            'fecha_sugerida.required' => 'Debe especificar una fecha sugerida.',
            'fecha_sugerida.date'     => 'La fecha sugerida debe ser una fecha válida.',
            'fecha_sugerida.after'    => 'La fecha sugerida debe ser posterior a hoy.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Errores de validación',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
