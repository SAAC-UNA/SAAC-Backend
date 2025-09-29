<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Campus;

class CampusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $table = (new Campus)->getTable(); // 'SEDE'
        $id    = $this->route('campus') ?? $this->route('id');

        $rules = [
            'nombre' => ['required', 'string', 'max:250'],
            'universidad_id' => ['required','integer','exists:UNIVERSIDAD,universidad_id'],
        ];

        // Si quisieras evitar duplicados de nombre dentro de la misma universidad:
        // $rules['nombre'][] = Rule::unique($table, 'nombre')
        //     ->where(fn($q) => $q->where('universidad_id', $this->universidad_id))
        //     ->ignore($id, 'sede_id');

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'universidad_id.required' => 'La universidad es obligatoria.',
            'universidad_id.exists'   => 'La universidad no existe.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'message' => 'Datos inválidos.',
                'errors'  => $validator->errors()
            ], 422)
        );
    }
}
