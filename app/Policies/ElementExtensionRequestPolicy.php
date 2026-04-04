<?php

namespace App\Policies;

use App\Models\ElementExtensionRequest;
use App\Models\User;

/**
 * Policy para solicitudes de ampliación de plazo — modelo flexible (elemento).
 * Opera sobre SOLICITUD_AMPLIACION_ELEMENTO.
 */
class ElementExtensionRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('solicitudes_ampliacion.view');
    }

    public function view(User $user, ElementExtensionRequest $solicitud): bool
    {
        if (!$user->can('solicitudes_ampliacion.view')) {
            return false;
        }

        if ($solicitud->usuario_id === $user->usuario_id) {
            return true;
        }

        return $user->can('solicitudes_ampliacion.approve');
    }

    public function create(User $user): bool
    {
        return $user->can('solicitudes_ampliacion.create');
    }

    public function update(User $user, ElementExtensionRequest $solicitud): bool
    {
        if (!$user->can('solicitudes_ampliacion.edit')) {
            return false;
        }

        if (strtolower($solicitud->estado) !== ElementExtensionRequest::ESTADO_PENDIENTE) {
            return false;
        }

        return $solicitud->usuario_id === $user->usuario_id ||
               $user->hasRole('Superusuario');
    }

    public function delete(User $user, ElementExtensionRequest $solicitud): bool
    {
        if (!$user->can('solicitudes_ampliacion.delete')) {
            return false;
        }

        if (strtolower($solicitud->estado) !== ElementExtensionRequest::ESTADO_PENDIENTE) {
            return false;
        }

        return $solicitud->usuario_id === $user->usuario_id ||
               $user->hasRole('Superusuario');
    }

    public function approve(User $user, ElementExtensionRequest $solicitud): bool
    {
        if (!$user->can('solicitudes_ampliacion.approve')) {
            return false;
        }

        return $solicitud->estado === ElementExtensionRequest::ESTADO_PENDIENTE;
    }

    public function reject(User $user, ElementExtensionRequest $solicitud): bool
    {
        if (!$user->can('solicitudes_ampliacion.reject')) {
            return false;
        }

        return $solicitud->estado === ElementExtensionRequest::ESTADO_PENDIENTE;
    }

    public function cancel(User $user, ElementExtensionRequest $solicitud): bool
    {
        if (!$user->can('solicitudes_ampliacion.cancel')) {
            return false;
        }

        if (strtolower($solicitud->estado) !== ElementExtensionRequest::ESTADO_PENDIENTE) {
            return false;
        }

        return $solicitud->usuario_id === $user->usuario_id ||
               $user->hasRole('Superusuario');
    }
}
