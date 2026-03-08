<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'archivo_id' => $this->archivo_id,
            'nombre_original' => $this->nombre_original,
            'fecha_subida' => $this->fecha_subida?->format('Y-m-d H:i:s'),
            
            // Tipo de evidencia: archivo o enlace
            'tipo' => $this->tipo ?? 'archivo',
            'url' => $this->when(
                $this->tipo === 'enlace',
                fn() => $this->url
            ),
            
            // Metadatos (solo para archivos físicos)
            'tamanio' => $this->when(
                $this->tipo === 'archivo',
                fn() => $this->tamanio
            ),
            'tipo_mime' => $this->when(
                $this->tipo === 'archivo',
                fn() => $this->tipo_mime
            ),
            
            // Acceso público
            'is_publico' => $this->is_publico,
            'url_publica' => $this->when(
                $this->isPubliclyAccessible(),
                fn() => $this->getPublicUrl()
            ),
            'link_expira_en' => $this->when(
                $this->is_publico && $this->link_expira_en,
                fn() => $this->link_expira_en?->format('Y-m-d H:i:s')
            ),
            
            // Relaciones
            'evidencia_id' => $this->evidencia_id,
            'evidencia' => $this->when(
                $this->relationLoaded('evidence'),
                fn() => [
                    'evidencia_id' => $this->evidence->evidencia_id,
                    'nombre' => $this->evidence->nombre,
                ]
            ),
            
            'usuario_id' => $this->usuario_id,
            'usuario' => $this->when(
                $this->relationLoaded('user'),
                fn() => [
                    'usuario_id' => $this->user->usuario_id,
                    'nombre_completo' => $this->user->nombre_completo,
                    'email' => $this->user->email,
                ]
            ),
            
            'proceso_id' => $this->proceso_id,
            'proceso' => $this->when(
                $this->relationLoaded('process'),
                fn() => [
                    'proceso_id' => $this->process->proceso_id,
                    'nombre' => $this->process->nombre ?? 'Proceso sin nombre',
                ]
            ),
        ];
    }
}
