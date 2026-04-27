<?php

namespace App\Policies;

use App\Models\AccreditationCycle;
use App\Models\AccreditationReport;
use App\Models\User;

/**
 * Policy para autorización de Informes de Acreditación.
 *
 * HU-027: Publicación de informe de acreditación aprobado.
 */
class AccreditationReportPolicy
{
    /**
     * Ver el listado público de informes.
     * No requiere autenticación — el controller no llama authorize() en index/showByCycle.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Ver un informe específico.
     * Si está publicado, cualquiera puede verlo. Si está despublicado, requiere permiso.
     */
    public function view(?User $user, AccreditationReport $report): bool
    {
        if ($report->isPublished()) {
            return true;
        }

        // Informe despublicado: solo usuarios con permiso de vista
        return $user?->can('informes_acreditacion.view') ?? false;
    }

    /**
     * Publicar un nuevo informe en un ciclo.
     * Solo usuarios con permiso informes_acreditacion.publish.
     */
    public function publish(User $user, AccreditationCycle $cycle): bool
    {
        return $user->can('informes_acreditacion.publish');
    }

    /**
     * Editar un informe (publicado o despublicado).
     * Superusuario, Administrador y Encargado de Acreditación (permiso informes_acreditacion.publish).
     */
    public function update(User $user, AccreditationReport $report): bool
    {
        return $user->hasRole('Superusuario') || $user->can('informes_acreditacion.publish');
    }

    /**
     * Despublicar un informe ya publicado.
     * Solo usuarios con permiso informes_acreditacion.unpublish.
     * La validación de estado (ya despublicado) es responsabilidad del servicio.
     */
    public function unpublish(User $user, AccreditationReport $report): bool
    {
        return $user->can('informes_acreditacion.unpublish');
    }

    /**
     * Eliminar un informe físicamente del sistema.
     * Superusuario y Administrador pueden eliminar; Encargado solo despublica.
     */
    public function delete(User $user, AccreditationReport $report): bool
    {
        return $user->hasRole('Superusuario') || $user->hasRole('Administrador');
    }

    /**
     * Descargar el PDF del informe.
     * Cualquier usuario autenticado con permiso de descarga.
     */
    public function download(User $user, AccreditationReport $report): bool
    {
        return $user->can('informes_acreditacion.download');
    }
}
