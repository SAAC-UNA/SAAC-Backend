<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImprovementCommitmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'compromiso_mejora_id' => $this->compromiso_mejora_id,
            'proceso_id' => $this->proceso_id,
            'descripcion' => $this->descripcion,
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'fecha_fin' => $this->fecha_fin?->format('Y-m-d'),
            'estado' => $this->estado,
            'activo' => $this->activo ?? true,
            'is_overdue' => $this->is_overdue,
            
            // Proceso relacionado
            'process' => $this->whenLoaded('process', function () {
                $processData = [
                    'proceso_id' => $this->process->proceso_id,
                    'tipo_proceso' => $this->process->tipo_proceso,
                    'ciclo_acreditacion_id' => $this->process->ciclo_acreditacion_id,
                ];

                // Si está cargado accreditationCycle, incluir info de carrera
                if ($this->process->relationLoaded('accreditationCycle')) {
                    $cycle = $this->process->accreditationCycle;
                    $processData['accreditation_cycle'] = [
                        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
                        'nombre' => $cycle->nombre,
                        'carrera_sede_id' => $cycle->carrera_sede_id,
                    ];

                    // Si está cargado careerCampus
                    if ($cycle->relationLoaded('careerCampus')) {
                        $careerCampus = $cycle->careerCampus;
                        $processData['accreditation_cycle']['career'] = $careerCampus->relationLoaded('career') ? [
                            'carrera_id' => $careerCampus->career->carrera_id,
                            'nombre' => $careerCampus->career->nombre,
                        ] : null;
                        $processData['accreditation_cycle']['campus'] = $careerCampus->relationLoaded('campus') ? [
                            'sede_id' => $careerCampus->campus->sede_id,
                            'nombre' => $careerCampus->campus->nombre,
                        ] : null;
                    }
                }

                return $processData;
            }),
            
            // Selecciones con jerarquía completa (del accessor)..
            'selecciones' => $this->selecciones,
            
            // Asignaciones de evidencias con comentarios del pivote
            'assigned_evidences' => $this->whenLoaded('assignedEvidences', function () {
                return $this->assignedEvidences->map(function ($assignment) {
                    return [
                        'evidencia_asignacion_id' => $assignment->evidencia_asignacion_id,
                        'evidencia_id' => $assignment->evidencia_id,
                        'usuario_id' => $assignment->usuario_id,
                        'fecha_asignacion' => $assignment->fecha_asignacion?->format('Y-m-d H:i:s'),
                        'fecha_limite' => $assignment->fecha_limite?->format('Y-m-d'),
                        'estado' => $assignment->estado,
                        
                        // Comentario del pivote (específico del compromiso)
                        'comentario' => $assignment->pivot->comentario ?? null,
                        
                        // Relaciones si están cargadas
                        'evidence' => $assignment->relationLoaded('evidence') ? [
                            'evidencia_id' => $assignment->evidence->evidencia_id,
                            'nomenclatura' => $assignment->evidence->nomenclatura,
                            'descripcion' => $assignment->evidence->descripcion,
                        ] : null,
                        'user' => $assignment->relationLoaded('user') ? [
                            'usuario_id' => $assignment->user->usuario_id,
                            'nombre' => $assignment->user->nombre,
                            'email' => $assignment->user->email,
                        ] : null,
                    ];
                });
            }),
            
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
