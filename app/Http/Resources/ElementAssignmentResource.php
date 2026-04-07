<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElementAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'elemento_asignacion_id'       => $this->elemento_asignacion_id,
            'elemento_id'                  => $this->elemento_id,
            'usuario_id'                   => $this->usuario_id,
            'proceso_id'                   => $this->proceso_id,
            'estado'                       => $this->estado,
            'fecha_limite'                 => optional($this->fecha_limite)->format('Y-m-d'),
            'comentario'                   => $this->comentario,
            'created_at'                   => optional($this->created_at)->toISOString(),
            'updated_at'                   => optional($this->updated_at)->toISOString(),

            // Flags calculados via withExists() en el servicio
            'has_pending_extension_request' => (bool) ($this->has_pending_extension_request ?? false),
            'has_uploaded_files'            => (bool) ($this->has_uploaded_files ?? false),
            'is_returned_for_changes'       => (bool) ($this->is_returned_for_changes ?? false),

            'element' => $this->whenLoaded('element', fn () => [
                'elemento_id'  => $this->element->elemento_id,
                'nombre'       => $this->element->nombre,
                'tipo'         => $this->element->tipo,
                'descripcion'  => $this->element->descripcion,
                'nomenclatura' => $this->element->nomenclatura,
            ]),

            'process' => $this->whenLoaded('process', fn () => [
                'proceso_id'            => $this->process->proceso_id,
                'nombre'                => $this->process->nombre,
                'ciclo_acreditacion_id' => $this->process->ciclo_acreditacion_id,
            ]),

            'user' => $this->whenLoaded('user', fn () => [
                'usuario_id' => $this->user->usuario_id,
                'nombre'     => $this->user->nombre,
            ]),

            // Retroalimentación (solo presente en findById)
            'comments' => $this->whenLoaded('comments', fn () =>
                $this->comments->map(fn ($c) => [
                    'id'     => $c->comentario_id,
                    'texto'  => $c->texto,
                    'autor'  => $c->user->nombre ?? 'Evaluador',
                    'fecha'  => optional($c->created_at)->toISOString(),
                ])
            ),
        ];
    }
}
