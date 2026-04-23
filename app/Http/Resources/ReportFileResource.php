<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportFileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'informe_archivo_id' => $this->informe_archivo_id,
            'nombre_original' => $this->nombre_original,
            'fecha_subida' => $this->fecha_subida?->format('Y-m-d H:i:s'),
            'tipo' => $this->tipo ?? 'archivo',
            'url' => $this->when(
                $this->tipo === 'enlace',
                fn() => $this->url
            ),
            'tamanio' => $this->when(
                $this->tipo === 'archivo',
                fn() => $this->tamanio
            ),
            'tipo_mime' => $this->when(
                $this->tipo === 'archivo',
                fn() => $this->tipo_mime
            ),
            'is_publico' => $this->is_publico,
            'token_publico' => $this->token_publico,
            'url_publica' => $this->when(
                $this->isPubliclyAccessible(),
                fn() => $this->getPublicUrl()
            ),
            'url_publica_carpeta' => $this->when(
                $this->isPubliclyAccessible(),
                function () {
                    $baseUrl = (string) config('app.frontend_url', config('app.url'));

                    return rtrim($baseUrl, '/') . '/p-informes/' . $this->token_publico;
                }
            ),
            'link_expira_en' => $this->when(
                $this->is_publico && $this->link_expira_en,
                fn() => $this->link_expira_en?->format('Y-m-d H:i:s')
            ),
            'usuario_id' => $this->usuario_id,
            'usuario' => $this->when(
                $this->relationLoaded('user'),
                fn() => [
                    'usuario_id' => $this->user->usuario_id,
                    'nombre_completo' => $this->user->nombre,
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
