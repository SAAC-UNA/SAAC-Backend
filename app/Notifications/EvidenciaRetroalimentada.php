<?php

namespace App\Notifications;

use App\Models\Evidence;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación enviada automáticamente al profesor/responsable de una evidencia
 * cuando el Encargado de Acreditación la retroalimenta (HU-013).
 *
 * Se dispara siempre — sin opción de desactivar — porque el profesor necesita
 * saber que tiene correcciones pendientes sin depender de revisar el sistema.
 *
 * Destinatario : usuarios con asignación activa en la evidencia (profesores)
 * Canal        : email (mismo patrón que ExtensionRequestCreated / HU-016)
 *
 * PARA DESACTIVAR (si alguna vez se requiere):
 * - Comenta la llamada Notification::send() en EvidenceService::retroalimentar()
 */
class EvidenciaRetroalimentada extends Notification
{
    use Queueable;

    /**
     * @param Evidence $evidence  La evidencia que fue retroalimentada
     * @param User     $reviewer  El encargado que realizó la acción
     * @param string   $comentario Texto del comentario registrado
     * @param string   $nuevoEstado 'observada' o 'validada'
     */
    public function __construct(
        protected Evidence $evidence,
        protected User $reviewer,
        protected string $comentario,
        protected string $nuevoEstado,
    ) {}

    /**
     * Solo email — mismo canal que el resto del sistema.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Contenido del email.
     *
     * El $notifiable es el profesor (User) que recibe la notificación.
     * El asunto y cuerpo cambian según si la evidencia fue observada o validada,
     * para que el profesor entienda de inmediato qué acción debe tomar.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $esObservada = $this->nuevoEstado === 'Observada';

        $asunto = $esObservada
            ? "Evidencia observada: requiere corrección — SAAC"
            : "Evidencia validada — SAAC";

        $lineaPrincipal = $esObservada
            ? "La evidencia **{$this->evidence->nomenclatura}** ha sido **observada** y requiere correcciones."
            : "La evidencia **{$this->evidence->nomenclatura}** ha sido **validada** satisfactoriamente.";

        $mail = (new MailMessage)
            ->subject($asunto)
            ->greeting('Hola ' . $notifiable->nombre . ',')
            ->line($lineaPrincipal)
            ->line('**Evidencia:** ' . $this->evidence->nomenclatura . ' — ' . $this->evidence->descripcion)
            ->line('**Estado asignado:** ' . strtoupper($this->nuevoEstado))
            ->line('**Comentario del evaluador:** ' . $this->comentario)
            ->line('**Evaluador:** ' . $this->reviewer->nombre)
            ->line('**Fecha:** ' . now()->format('d/m/Y H:i'));

        if ($esObservada) {
            $mail->line('Por favor, revisa el comentario y realiza las correcciones necesarias en el sistema.');
        }

        return $mail;
    }

    /**
     * Representación en array (por si en el futuro se agrega canal database).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'evidencia_id'  => $this->evidence->evidencia_id,
            'nomenclatura'  => $this->evidence->nomenclatura,
            'nuevo_estado'  => $this->nuevoEstado,
            'comentario'    => $this->comentario,
            'evaluador'     => $this->reviewer->nombre,
        ];
    }
}
