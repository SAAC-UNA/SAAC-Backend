<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;
use App\Models\Evidence;

/**
 * Seeder de Notificaciones de Prueba
 * 
 * HU-018: Notificaciones automáticas
 * 
 * Crea notificaciones de ejemplo para diferentes tipos de eventos
 */
class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📬 Creando notificaciones de prueba...');

        // Obtener primer usuario
        $usuario = User::first();
        
        if (!$usuario) {
            $this->command->warn('⚠️  No se encontraron usuarios. NotificationSeeder se salta.');
            return;
        }

        // Obtener primera evidencia (si existe)
        $evidencia = Evidence::first();

        // 1. Notificación de asignación de evidencia
        Notification::create([
            'usuario_id' => $usuario->usuario_id,
            'tipo_evento' => Notification::TIPO_ASIGNACION_EVIDENCIA,
            'canal' => Notification::CANAL_AMBOS,
            'titulo' => 'Nueva evidencia asignada',
            'mensaje' => 'Se te ha asignado la evidencia "Documentación del Plan de Estudios" (E-01). Fecha límite: 30/01/2026',
            'leida' => false,
            'relacionado_type' => $evidencia ? get_class($evidencia) : null,
            'relacionado_id' => $evidencia?->evidencia_id,
            'enlace' => '/evidencias/' . ($evidencia?->evidencia_id ?? '1'),
            'estado_email' => Notification::EMAIL_ENVIADO,
            'metadatos' => [
                'evidencia_id' => $evidencia?->evidencia_id ?? 1,
                'fecha_limite' => '2026-01-30',
            ],
        ]);

        // 2. Notificación de archivo subido (confirmación)
        Notification::create([
            'usuario_id' => $usuario->usuario_id,
            'tipo_evento' => Notification::TIPO_CARGA_ARCHIVO,
            'canal' => Notification::CANAL_INTERNO,
            'titulo' => 'Archivo subido correctamente',
            'mensaje' => 'Tu archivo "plan_estudios_2024.pdf" se ha subido exitosamente.',
            'leida' => true,
            'fecha_lectura' => now()->subDays(2),
            'enlace' => '/archivos/1',
            'metadatos' => [
                'archivo_id' => 1,
                'nombre_archivo' => 'plan_estudios_2024.pdf',
                'tamanio' => '2.5 MB',
            ],
        ]);

        // 3. Notificación de vencimiento próximo (crítica)
        Notification::create([
            'usuario_id' => $usuario->usuario_id,
            'tipo_evento' => Notification::TIPO_VENCIMIENTO_PLAZO,
            'canal' => Notification::CANAL_AMBOS,
            'titulo' => '⚠️ Plazo vence en 3 días',
            'mensaje' => 'La evidencia "Informes de Evaluación Docente" vence el 20/01/2026. ¡Acción urgente requerida!',
            'leida' => false,
            'relacionado_type' => $evidencia ? get_class($evidencia) : null,
            'relacionado_id' => $evidencia?->evidencia_id,
            'enlace' => '/evidencias/' . ($evidencia?->evidencia_id ?? '2'),
            'estado_email' => Notification::EMAIL_ENVIADO,
            'metadatos' => [
                'evidencia_id' => $evidencia?->evidencia_id ?? 2,
                'dias_restantes' => 3,
                'urgente' => true,
            ],
        ]);

        // 4. Notificación de aprobación de criterio
        Notification::create([
            'usuario_id' => $usuario->usuario_id,
            'tipo_evento' => Notification::TIPO_APROBACION_CRITERIO,
            'canal' => Notification::CANAL_INTERNO,
            'titulo' => 'Criterio aprobado',
            'mensaje' => 'El criterio "1.1.1 - Información y promoción" ha sido aprobado exitosamente.',
            'leida' => true,
            'fecha_lectura' => now()->subDays(5),
            'enlace' => '/criterios/1',
            'metadatos' => [
                'criterio_id' => 1,
                'aprobado_por' => 'Coordinador de Acreditación',
            ],
        ]);

        // 5. Notificación de solicitud de ampliación
        Notification::create([
            'usuario_id' => $usuario->usuario_id,
            'tipo_evento' => Notification::TIPO_SOLICITUD_AMPLIACION,
            'canal' => Notification::CANAL_AMBOS,
            'titulo' => 'Nueva solicitud de ampliación',
            'mensaje' => 'El profesor Juan Pérez ha solicitado ampliar el plazo de la evidencia E-05 hasta el 15/02/2026.',
            'leida' => false,
            'enlace' => '/solicitudes-ampliacion/1',
            'estado_email' => Notification::EMAIL_ENVIADO,
            'metadatos' => [
                'solicitud_id' => 1,
                'solicitante' => 'Juan Pérez',
                'nueva_fecha' => '2026-02-15',
            ],
        ]);

        // 6. Notificación de comentario nuevo
        Notification::create([
            'usuario_id' => $usuario->usuario_id,
            'tipo_evento' => Notification::TIPO_COMENTARIO_NUEVO,
            'canal' => Notification::CANAL_INTERNO,
            'titulo' => 'Nuevo comentario en evidencia',
            'mensaje' => 'María López ha comentado en la evidencia "Actas de Reunión": "Favor revisar el formato del acta 3"',
            'leida' => false,
            'enlace' => '/evidencias/3#comentarios',
            'metadatos' => [
                'evidencia_id' => 3,
                'comentario_autor' => 'María López',
            ],
        ]);

        // 7. Notificación de rechazo de evidencia
        Notification::create([
            'usuario_id' => $usuario->usuario_id,
            'tipo_evento' => Notification::TIPO_RECHAZO_EVIDENCIA,
            'canal' => Notification::CANAL_AMBOS,
            'titulo' => 'Evidencia rechazada',
            'mensaje' => 'La evidencia "Informes Estadísticos" ha sido rechazada. Motivo: Datos incompletos. Por favor, revisa y vuelve a subir.',
            'leida' => false,
            'enlace' => '/evidencias/4',
            'estado_email' => Notification::EMAIL_PENDIENTE,
            'metadatos' => [
                'evidencia_id' => 4,
                'motivo_rechazo' => 'Datos incompletos',
                'rechazado_por' => 'Coordinador',
            ],
        ]);

        $this->command->info('✅ 7 notificaciones de prueba creadas exitosamente');
    }
}
