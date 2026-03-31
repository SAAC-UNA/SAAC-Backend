<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ElementAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'elemento_id'  => ['required', 'integer', 'exists:ELEMENTO,elemento_id'],
            'proceso_id'   => ['required', 'integer', 'exists:PROCESO,proceso_id'],
            'usuarios'     => ['nullable', 'array'],
            'usuarios.*'   => ['integer', 'exists:USUARIO,usuario_id'],
            'roles'        => ['nullable', 'array'],
            'roles.*'      => ['integer', 'exists:roles,id'],
            'fecha_limite' => ['nullable', 'date'],
            'comentario'   => ['nullable', 'string', 'max:1000'],
            'asignado_por' => ['nullable', 'integer', 'exists:USUARIO,usuario_id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $users = $this->input('usuarios', []);
            $roles = $this->input('roles', []);

            if (empty($users) && empty($roles)) {
                $validator->errors()->add(
                    'usuarios',
                    'At least one user or role must be specified for the assignment.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'elemento_id.required' => 'The element is required.',
            'elemento_id.exists'   => 'The specified element does not exist.',
            'proceso_id.required'  => 'The process is required.',
            'proceso_id.exists'    => 'The specified process does not exist.',
            'usuarios.*.exists'    => 'One or more users do not exist.',
            'roles.*.exists'       => 'One or more roles do not exist.',
            'fecha_limite.date'    => 'The deadline must be a valid date.',
        ];
    }
}
