<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $notificationTitle;
    public $notificationMessage;
    public $actionUrl;
    public $actionText;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $userName = 'Usuario',
        string $notificationTitle = 'Notificación de prueba',
        string $notificationMessage = 'Este es un correo de prueba del sistema SAAC-UNA',
        ?string $actionUrl = null,
        string $actionText = 'Ver en el sistema'
    ) {
        $this->userName = $userName;
        $this->notificationTitle = $notificationTitle;
        $this->notificationMessage = $notificationMessage;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔔 ' . $this->notificationTitle . ' - SAAC UNA',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
