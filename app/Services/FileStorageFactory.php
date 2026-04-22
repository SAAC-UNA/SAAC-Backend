<?php

namespace App\Services;

use App\Contracts\FileStorageContract;
use App\Models\File;

/**
 * Factory que resuelve la estrategia de almacenamiento correcta.
 *
 * Es el único lugar donde existe el "if tradicional / if flexible".
 * Agregar un nuevo modelo = agregar un `elseif` aquí + crear la nueva clase.
 * El controller, los tests y las estrategias existentes no se tocan (OCP).
 */
class FileStorageFactory
{
    /**
     * Resuelve la estrategia en base a los IDs presentes en el request.
     *
     * @param  int|null $evidenciaId ID de evidencia (modelo tradicional)
     * @param  int|null $elementoId  ID de elemento (modelo flexible)
     * @throws \InvalidArgumentException si ningún ID es provisto
     */
    public function make(?int $evidenciaId, ?int $elementoId): FileStorageContract
    {
        if ($evidenciaId !== null) {
            return app(TradicionalFileService::class);
        }

        if ($elementoId !== null) {
            return app(FlexibleFileService::class);
        }

        throw new \InvalidArgumentException(
            'Se requiere evidencia_id (modelo tradicional) o elemento_id (modelo flexible).'
        );
    }

    /**
     * Resuelve la estrategia inspeccionando un archivo ya persistido.
     * Útil en makePublic, delete, download donde no tenemos el request original.
     *
     * Caso especial: archivos de informes de acreditación (HU-027) no tienen
     * evidencia_id ni elemento_id. Se usa FlexibleFileService como fallback
     * ya que comparte el mismo disco (simulated_nas) y las operaciones model-agnostic
     * (getDisk, makePublic, revokePublicAccess, deleteFile) son idénticas en ambos.
     */
    public function makeFromFile(File $archivo): FileStorageContract
    {
        if ($archivo->evidencia_id === null && $archivo->elemento_id === null) {
            // Informe de acreditación: sin evidencia ni elemento asignado.
            return app(FlexibleFileService::class);
        }

        return $this->make($archivo->evidencia_id, $archivo->elemento_id);
    }
}
