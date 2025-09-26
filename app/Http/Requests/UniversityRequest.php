<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\University;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UniversityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    protected function failedValidation(Validator $v)
    {
    throw new HttpResponseException(
        response()->json(['message'=>'Datos inválidos.','errors'=>$v->errors()], 422)
    );
    }

    public function rules(): array
    {
        // Nombre real de la tabla desde el modelo (UNIVERSIDAD)
        $table = (new University)->getTable();

        // Detecta el id desde la ruta. Con apiResource, normalmente es {universidad}.
        // Si tu ruta usa {id}, también queda cubierto.
        $id = $this->route('universidad') ?? $this->route('id');

        // Regla base de unicidad
        $unique = Rule::unique($table, 'nombre');

        // Si hay id (update), ignorar ese registro en la validación de unique
        if ($id) {
            $unique = $unique->ignore($id, 'universidad_id');
        }

        return [
            'nombre' => ['required', 'string', 'max:250', $unique],
        ];
    }

    public function messages(): array
    {
        // Mensajes iguales a los que ya usas
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique'   => $this->route('universidad') || $this->route('id')
                                ? 'Ya existe otra universidad con ese nombre.'
                                : 'Ya existe una universidad con ese nombre.',
        ];
    }
}
