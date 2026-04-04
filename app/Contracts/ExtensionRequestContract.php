<?php

namespace App\Contracts;

use App\Models\ExtensionRequest;

/**
 * Contrato para servicios de solicitudes de ampliación de plazo.
 *
 * Define las operaciones compartidas entre el modelo tradicional
 * (evidencia_asignacion_id) y el modelo flexible (elemento_asignacion_id).
 *
 * La creación de solicitudes NO está en este contrato porque las firmas
 * difieren por modelo:
 *   - Tradicional: createRequest(array $data, int $userId)
 *   - Flexible:    createRequest(ElementAssignment $assignment, array $data, int $userId)
 */
interface ExtensionRequestContract
{
    public function getAll(array $filters = []): mixed;

    public function getPending(array $filters = []): mixed;

    public function getByUser(int $usuarioId, array $filters = []): mixed;

    public function findById(int $id): ?ExtensionRequest;

    public function approve(int $solicitudId, int $resolutorId, ?string $justificacion = null): ExtensionRequest;

    public function reject(int $solicitudId, int $resolutorId, string $justificacion): ExtensionRequest;
}
