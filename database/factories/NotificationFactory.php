<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $tipoEvento = $this->faker->randomElement([
            Notification::TIPO_ASIGNACION_EVIDENCIA,
            Notification::TIPO_CARGA_ARCHIVO,
            Notification::TIPO_VENCIMIENTO_PLAZO,
            Notification::TIPO_DEVOLUCION_OBSERVACION,
            Notification::TIPO_APROBACION_CRITERIO,
            Notification::TIPO_APROBACION_EVIDENCIA,
            Notification::TIPO_RECHAZO_EVIDENCIA,
            Notification::TIPO_SOLICITUD_AMPLIACION,
            Notification::TIPO_RESPUESTA_AMPLIACION,
            Notification::TIPO_COMENTARIO_NUEVO,
            Notification::TIPO_ACTUALIZACION_SISTEMA,
        ]);

        return [
            'usuario_id' => User::factory(),
            'tipo_evento' => $tipoEvento,
            'canal' => Notification::CANAL_INTERNO,
            'titulo' => $this->faker->sentence(4),
            'mensaje' => $this->faker->paragraph(),
            'leida' => false,
            'fecha_lectura' => null,
            'enlace' => $this->faker->boolean(70) ? $this->faker->url() : null,
            'estado_email' => Notification::EMAIL_NO_APLICA,
            'detalle_error' => null,
            'metadatos' => null,
        ];
    }
}
