<?php

namespace App\Notifications;

use App\Models\ExtensionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación enviada cuando se crea una solicitud de ampliación.
 * 
 * PARA DESACTIVAR:
 * - Comenta la línea en ExtensionRequestService->createRequest() donde dice "Notification::send()"
 * 
 * PARA ELIMINAR COMPLETAMENTE:
 * - Elimina este archivo
 * - Elimina las líneas comentadas con "HU-16: NOTIFICACIÓN" en ExtensionRequestService
 */
class ExtensionRequestCreated extends Notification
{
    use Queueable;

    protected $solicitud;

    /**
     * Crear nueva instancia de notificación.
     * 
     * @param ExtensionRequest $solicitud La solicitud recién creada
     */
    public function __construct(ExtensionRequest $solicitud)
    {
        $this->solicitud = $solicitud;
    }

    /**
     * Canales por los que se envía la notificación.
     * 
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // Solo email por ahora
    }

    /**
     * Contenido del email que se envía.
     * 
     * @param object $notifiable El usuario que recibe la notificación (encargado de acreditación)
     */
    public function toMail(object $notifiable): MailMessage
    {
        $solicitante = $this->solicitud->user;
        
        return (new MailMessage)
            ->subject('Nueva Solicitud de Ampliación - SAAC')
            ->greeting('Hola ' . $notifiable->nombre . ',')
            ->line('Se ha recibido una nueva solicitud de ampliación.')
            ->line('**Solicitante:** ' . $solicitante->nombre)
            ->line('**Motivo:** ' . $this->solicitud->motivo)
            ->line('**Fecha sugerida:** ' . $this->solicitud->fecha_sugerida->format('d/m/Y'))
            ->action('Revisar Solicitud', url('/solicitudes-ampliacion/' . $this->solicitud->solicitud_ampliacion_id))
            ->line('Por favor, revisa y gestiona esta solicitud lo antes posible.');
    }

    /**
     * Representación en array (para base de datos o notificaciones en el sistema).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'solicitud_ampliacion_id' => $this->solicitud->solicitud_ampliacion_id,
            'solicitante' => $this->solicitud->user->nombre,
            'motivo' => $this->solicitud->motivo,
            'fecha_sugerida' => $this->solicitud->fecha_sugerida->toDateString(),
        ];
    }
}
