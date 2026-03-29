<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Evidence;

/**
 * FilterEvidenceRequest - Validación de filtros para HU-012
 * 
 * Este Request cumple con el Criterio de Aceptación #2:
 * "Validación de parámetros de entrada"
 * 
 * Propósito:
 * - Validar que los filtros tengan el formato correcto
 * - Rechazar búsquedas con datos inválidos
 * - Retornar mensajes de error claros al frontend
 * 
 * Ejemplo de uso en controller:
 * public function filter(FilterEvidenceRequest $request) {
 *     $validatedData = $request->validated(); // Solo llega aquí si pasa validación
 * }
 * 
 * @package App\Http\Requests
 * @see EvidenceController::filter()
 */
class FilterEvidenceRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer este request.
     * 
     * Retornamos true porque la autorización se maneja en el middleware auth:sanctum
     * y en la lógica del service (restricciones por rol).
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para filtros de evidencias.
     * 
     * Todos los filtros son OPCIONALES (nullable) porque el usuario
     * puede elegir filtrar solo por uno o combinar varios.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Filtro por Dimensión
            // Ejemplo: { "dimension_id": 1 } busca evidencias dentro de la dimensión "Gestión Curricular"
            'dimension_id' => [
                'nullable',
                'integer',
                'exists:DIMENSION,dimension_id',
            ],

            // Filtro por Componente
            // Ejemplo: { "componente_id": 2 } busca evidencias del componente "Perfil de Egreso"
            'componente_id' => [
                'nullable',
                'integer',
                'exists:COMPONENTE,componente_id',
            ],

            // Filtro por Estándar
            // Ejemplo: { "estandar_id": 5 } busca evidencias del criterio que tiene ese estándar
            'estandar_id' => [
                'nullable',
                'integer',
                'exists:ESTANDAR,estandar_id',
            ],

            // Filtro por Criterio
            // Ejemplo: { "criterio_id": 4 } busca evidencias del criterio "2.1 - Plan de estudios"
            'criterio_id' => [
                'nullable',           // Opcional, puede no venir
                'integer',            // Debe ser número entero
                'exists:CRITERIO,criterio_id', // Debe existir en la tabla CRITERIO
            ],

            // Filtro por Responsable (usuario asignado)
            // Ejemplo: { "responsable_id": 5 } busca evidencias asignadas a Ana García
            'responsable_id' => [
                'nullable',
                'integer',
                'exists:USUARIO,usuario_id', // Debe existir en la tabla USUARIO
            ],

            // Filtro por Rango de Fechas (fecha de publicación)
            // Ejemplo: { "fecha_desde": "2025-01-01", "fecha_hasta": "2025-12-31" }
            'fecha_desde' => [
                'nullable',
                'date',              // Formato válido: YYYY-MM-DD
                'before_or_equal:fecha_hasta', // No puede ser después de fecha_hasta
            ],
            'fecha_hasta' => [
                'nullable',
                'date',
                'after_or_equal:fecha_desde',  // No puede ser antes de fecha_desde
            ],

            // Filtro por Estado de Evidencia
            // Ejemplo: { "estado": "aprobado" } busca evidencias aprobadas
            'estado' => [
                'nullable',
                'string',
                \Illuminate\Validation\Rule::in(Evidence::ESTADOS),
            ],

            // Filtro por Rol del responsable
            // Ejemplo: { "rol_id": 3 } busca evidencias asignadas a usuarios con rol "Evaluador"
            'rol_id' => [
                'nullable',
                'integer',
                'exists:roles,id',  // Tabla de Spatie Permission
            ],

            // Parámetros de Ordenamiento
            // Ejemplo: { "sort_by": "fecha", "sort_order": "desc" }
            'sort_by' => [
                'nullable',
                'string',
                'in:fecha,nomenclatura,descripcion,estado', // Solo estos valores permitidos
            ],
            'sort_order' => [
                'nullable',
                'string',
                'in:asc,desc',      // Ascendente o descendente
            ],

            // Paginación
            // Ejemplo: { "per_page": 20 } muestra 20 resultados por página
            'per_page' => [
                'nullable',
                'integer',
                'min:5',            // Mínimo 5 resultados por página
                'max:100',          // Máximo 100 (evita queries gigantes)
            ],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     * 
     * Estos mensajes se muestran al frontend cuando la validación falla.
     * Cumplen con el criterio: "muestra un mensaje de error"
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Mensajes para dimension_id
            'dimension_id.integer' => 'La dimensión debe ser un número entero.',
            'dimension_id.exists'  => 'La dimensión seleccionada no existe en el sistema.',

            // Mensajes para componente_id
            'componente_id.integer' => 'El componente debe ser un número entero.',
            'componente_id.exists'  => 'El componente seleccionado no existe en el sistema.',

            // Mensajes para estandar_id
            'estandar_id.integer' => 'El estándar debe ser un número entero.',
            'estandar_id.exists'  => 'El estándar seleccionado no existe en el sistema.',

            // Mensajes para criterio_id
            'criterio_id.integer' => 'El criterio debe ser un número entero.',
            'criterio_id.exists' => 'El criterio seleccionado no existe en el sistema.',

            // Mensajes para responsable_id
            'responsable_id.integer' => 'El responsable debe ser un número entero.',
            'responsable_id.exists' => 'El responsable seleccionado no existe en el sistema.',

            // Mensajes para fechas
            'fecha_desde.date' => 'La fecha inicial no es válida. Formato esperado: YYYY-MM-DD.',
            'fecha_desde.before_or_equal' => 'La fecha inicial no puede ser posterior a la fecha final.',
            'fecha_hasta.date' => 'La fecha final no es válida. Formato esperado: YYYY-MM-DD.',
            'fecha_hasta.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',

            // Mensajes para estado_evidencia_id
            'estado_evidencia_id.integer' => 'El estado de evidencia debe ser un número entero.',
            'estado_evidencia_id.exists' => 'El estado de evidencia seleccionado no existe.',

            // Mensajes para rol_id
            'rol_id.integer' => 'El rol debe ser un número entero.',
            'rol_id.exists' => 'El rol seleccionado no existe en el sistema.',

            // Mensajes para ordenamiento
            'sort_by.in' => 'El campo de ordenamiento debe ser: fecha, nomenclatura, descripcion o estado.',
            'sort_order.in' => 'El orden debe ser: asc (ascendente) o desc (descendente).',

            // Mensajes para paginación
            'per_page.integer' => 'El número de resultados por página debe ser un número entero.',
            'per_page.min' => 'Debe mostrar al menos :min resultados por página.',
            'per_page.max' => 'No se puede mostrar más de :max resultados por página.',
        ];
    }

    /**
     * Nombres personalizados de los campos para los mensajes de error.
     * 
     * Mejora la legibilidad de los errores automáticos de Laravel.
     * 
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'dimension_id'  => 'dimensión',
            'componente_id' => 'componente',
            'estandar_id'   => 'estándar',
            'criterio_id'   => 'criterio',
            'responsable_id' => 'responsable',
            'fecha_desde' => 'fecha inicial',
            'fecha_hasta' => 'fecha final',
            'estado_evidencia_id' => 'estado de evidencia',
            'rol_id' => 'rol',
            'sort_by' => 'campo de ordenamiento',
            'sort_order' => 'orden',
            'per_page' => 'resultados por página',
        ];
    }
}
