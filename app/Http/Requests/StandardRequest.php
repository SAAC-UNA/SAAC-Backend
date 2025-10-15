<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule; // NUEVO
use App\Models\Standard;

class StandardRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isUpdate = in_array($this->method(), ['PUT','PATCH']);
        $id = $this->route('estandar') ?? $this->route('id');

        $rules = [
            'criterio_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:CRITERIO,criterio_id',
            ],
            'descripcion' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:250',
                'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9 .,\-:;]+$/',
            ],
        ];

        // NUEVO: si en UPDATE cambian 'descripcion', exigimos 'criterio_id'
        if ($isUpdate) {
            $rules['criterio_id'][] = 'required_with:descripcion'; // NUEVO
        }

        // NUEVO: unicidad por par (criterio_id, descripcion)
        $unique = Rule::unique((new Standard)->getTable(), 'descripcion') // NUEVO
            ->where(fn($q) => $q->where('criterio_id', $this->input('criterio_id'))); // NUEVO
        if ($isUpdate && $id) { // NUEVO
            $unique = $unique->ignore($id, 'estandar_id'); // NUEVO (ajusta si tu PK se llama distinto)
        }
        $rules['descripcion'][] = $unique; // NUEVO

        return $rules;
    }

    public function messages(): array
    {
        return [
            'criterio_id.required' => 'El criterio es obligatorio.',
            'criterio_id.exists'   => 'El criterio no existe.',
            'criterio_id.required_with' => 'Debes enviar el criterio cuando actualizas la descripción.', // NUEVO
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.regex'    => 'La descripción solo puede contener letras, números, espacios, puntos, comas, guiones, dos puntos y punto y coma.',
            'descripcion.max'      => 'La descripción no puede superar los 250 caracteres.',
            'descripcion.unique'   => 'Ya existe un estándar con esa descripción en este criterio.', // NUEVO
        ];
    }

    // Reproduce “al menos un campo” para update (sin cambios)
    public function withValidator($validator)
    {
        if (in_array($this->method(), ['PUT','PATCH'])) {
            $validator->after(function ($v) {
                if (!$this->hasAny(['criterio_id','descripcion'])) {
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
