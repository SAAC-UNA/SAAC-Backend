<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestNotificationMail;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email} {--name=Usuario}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un correo de prueba para verificar la configuración SMTP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $name = $this->option('name');

        $this->info("📧 Enviando correo de prueba a: {$email}");
        $this->newLine();

        try {
            Mail::to($email)->send(new TestNotificationMail(
                userName: $name,
                notificationTitle: 'Prueba de Configuración de Correo',
                notificationMessage: "Este es un correo de prueba del sistema SAAC-UNA.\n\nSi recibes este mensaje, significa que la configuración SMTP está funcionando correctamente.\n\nFecha y hora del envío: " . now()->format('d/m/Y H:i:s'),
                actionUrl: config('app.url'),
                actionText: 'Ir al sistema SAAC'
            ));

            $this->newLine();
            $this->info('✅ Correo enviado exitosamente!');
            $this->newLine();
            
            $this->line('Configuración utilizada:');
            $this->table(
                ['Parámetro', 'Valor'],
                [
                    ['MAIL_MAILER', config('mail.default')],
                    ['MAIL_HOST', config('mail.mailers.smtp.host')],
                    ['MAIL_PORT', config('mail.mailers.smtp.port')],
                    ['MAIL_ENCRYPTION', config('mail.mailers.smtp.encryption')],
                    ['MAIL_FROM_ADDRESS', config('mail.from.address')],
                    ['MAIL_FROM_NAME', config('mail.from.name')],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Error al enviar el correo:');
            $this->error($e->getMessage());
            $this->newLine();
            
            $this->warn('Verifica lo siguiente:');
            $this->line('1. MAIL_MAILER debe ser "smtp" (actualmente: ' . config('mail.default') . ')');
            $this->line('2. MAIL_USERNAME debe tener tu email de Gmail');
            $this->line('3. MAIL_PASSWORD debe tener tu App Password de Gmail (16 caracteres)');
            $this->line('4. Verificación en 2 pasos debe estar habilitada en tu cuenta Google');
            $this->newLine();

            return Command::FAILURE;
        }
    }
}
