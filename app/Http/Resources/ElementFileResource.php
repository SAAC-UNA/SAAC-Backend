<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource exclusiva para archivos del Modelo Flexible (HU-008).
 *
 * Expone los metadatos requeridos por las HU de subida flexible:
 *   - Autor (nombre + rol snapshot) — sin exponer usuario_id
 *   - Descripción del elemento (equivalente a descripcion de evidencia en modelo tradicional)
 *   - Sello de tiempo (fecha_subida ya existe en ARCHIVO)
 *   - Elemento asociado (reemplaza a evidencia en el modelo flexible)
 */
class ElementFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'archivo_id'      => $this->archivo_id,
            'nombre_original' => $this->nombre_original,
            'fecha_subida'    => $this->fecha_subida?->format('Y-m-d H:i:s'),

            // Tipo: archivo físico o enlace externo
            'tipo' => $this->tipo ?? 'archivo',
            'url'  => $this->when(
                $this->tipo === 'enlace',
                fn() => $this->url
            ),

            // Metadatos físicos (solo para tipo=archivo)
            'tamanio'   => $this->when($this->tipo === 'archivo', fn() => $this->tamanio),
            'tipo_mime' => $this->when($this->tipo === 'archivo', fn() => $this->tipo_mime),

            // Acceso público
            'is_publico'     => $this->is_publico,
            'token_publico'  => $this->token_publico,
            'url_publica'    => $this->when(
                $this->isPubliclyAccessible(),
                fn() => $this->getPublicUrl()
            ),
            'url_publica_carpeta' => $this->when(
                $this->isPubliclyAccessible(),
                function () {
                    $baseUrl = (string) config('app.frontend_url', config('app.url'));

                    return rtrim($baseUrl, '/') . '/p/' . $this->token_publico;
                }
            ),
            'link_expira_en' => $this->when(
                $this->is_publico && $this->link_expira_en,
                fn() => $this->link_expira_en?->format('Y-m-d H:i:s')
            ),

            // ── Elemento asociado ─────────────────────────────────────────────
            // Reemplaza a 'evidencia' del modelo tradicional.
            // 'descripcion' del elemento cumple el rol de "Descripción de la
            // evidencia" descrito en HU-008 para el modelo flexible.
            'elemento_id' => $this->elemento_id,
            'elemento'    => $this->when(
                $this->relationLoaded('elemento') && $this->elemento !== null,
                fn() => [
                    'elemento_id'  => $this->elemento->elemento_id,
                    'nomenclatura' => $this->elemento->nomenclatura,
                    'tipo'         => $this->elemento->tipo,
                    'descripcion'  => $this->elemento->descripcion,
                ]
            ),

            // ── Autor (HU: Registro de autor automático) ──────────────────────
            // Nombre + rol en el momento de la subida + sello de tiempo (fecha_subida).
            // usuario_id NO se expone aquí (se eliminó en revisión anterior).
            'autor' => $this->when(
                $this->relationLoaded('user') && $this->user !== null,
                fn() => [
                    'nombre' => $this->user->nombre,
                    'rol'    => $this->user->getRoleNames()->first() ?? 'Sin rol asignado',
                ]
            ),

            'proceso_id' => $this->proceso_id,
            'proceso'    => $this->when(
                $this->relationLoaded('process') && $this->process !== null,
                fn() => [
                    'proceso_id' => $this->process->proceso_id,
                    'nombre'     => $this->process->nombre ?? 'Proceso sin nombre',
                ]
            ),
        ];
    }
}
