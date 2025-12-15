<?php

namespace Database\Factories;

use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para generar datos de prueba de ExtensionRequest
 * 
 * Se usa en tests para crear solicitudes de ampliación falsas sin tocar datos reales.
 * 
 * Ejemplos de uso:
 * - ExtensionRequest::factory()->create(); // Crea 1 solicitud con datos aleatorios
 * - ExtensionRequest::factory()->count(5)->create(); // Crea 5 solicitudes
 * - ExtensionRequest::factory()->pendiente()->create(); // Crea 1 solicitud pendiente
 * - ExtensionRequest::factory()->aprobada()->create(); // Crea 1 solicitud aprobada
 */
class ExtensionRequestFactory extends Factory
{
    protected $model = ExtensionRequest::class;

    /**
     * Define los valores por defecto para crear una solicitud.
     * 
     * Faker genera datos falsos pero realistas:
     * - dateTimeBetween('-1 week', 'now'): Fecha entre hace 1 semana y ahora
     * - sentence(15): Frase de aproximadamente 15 palabras
     * - randomElement([...]): Elige uno de los valores al azar
     */
    public function definition()
    {
        return [
            // Crea una asignación de evidencia relacionada automáticamente
            'evidencia_asignacion_id' => EvidenceAssignment::factory(),
            
            // Crea un usuario solicitante automáticamente
            'usuario_id' => User::factory(),
            
            // Fecha de solicitud: entre hace 1 semana y hoy
            'fecha_solicitud' => $this->faker->dateTimeBetween('-1 week', 'now'),
            
            // Motivo: texto de 15 palabras aproximadamente
            'motivo' => $this->faker->sentence(15),
            
            // Fecha sugerida: entre mañana y dentro de 2 semanas
            'fecha_sugerida' => $this->faker->dateTimeBetween('+1 day', '+2 weeks'),
            
            // Estado: elige al azar entre los 3 estados posibles
            'estado' => $this->faker->randomElement(['pendiente', 'aprobada', 'rechazada']),
            
            // Por defecto, no está resuelta (estos campos son null)
            'fecha_resolucion' => null,
            'usuario_resolutor_id' => null,
            'justificacion' => null,
        ];
    }

    /**
     * Estado: Pendiente
     * 
     * Modifica el estado para crear solicitudes que AÚN NO han sido revisadas.
     * Uso: ExtensionRequest::factory()->pendiente()->create();
     */
    public function pendiente()
    {
        return $this->state(function (array $attributes) {
            return [
                'estado' => 'pendiente',
                'fecha_resolucion' => null,
                'usuario_resolutor_id' => null,
                'justificacion' => null,
            ];
        });
    }

    /**
     * Estado: Aprobada
     * 
     * Modifica el estado para crear solicitudes APROBADAS con:
     * - fecha_resolucion: cuándo fue aprobada
     * - usuario_resolutor_id: quién la aprobó
     * - justificacion: por qué fue aprobada
     * 
     * Uso: ExtensionRequest::factory()->aprobada()->create();
     */
    public function aprobada()
    {
        return $this->state(function (array $attributes) {
            return [
                'estado' => 'aprobada',
                // Fecha de resolución: entre ahora y dentro de 3 días
                'fecha_resolucion' => $this->faker->dateTimeBetween('now', '+3 days'),
                // Crea un usuario resolutor (quien aprobó)
                'usuario_resolutor_id' => User::factory(),
                // Justificación: texto de 20 palabras
                'justificacion' => $this->faker->sentence(20),
            ];
        });
    }

    /**
     * Estado: Rechazada
     * 
     * Modifica el estado para crear solicitudes RECHAZADAS con:
     * - fecha_resolucion: cuándo fue rechazada
     * - usuario_resolutor_id: quién la rechazó
     * - justificacion: por qué fue rechazada
     * 
     * Uso: ExtensionRequest::factory()->rechazada()->create();
     */
    public function rechazada()
    {
        return $this->state(function (array $attributes) {
            return [
                'estado' => 'rechazada',
                // Fecha de resolución: entre ahora y dentro de 3 días
                'fecha_resolucion' => $this->faker->dateTimeBetween('now', '+3 days'),
                // Crea un usuario resolutor (quien rechazó)
                'usuario_resolutor_id' => User::factory(),
                // Justificación: texto de 20 palabras
                'justificacion' => $this->faker->sentence(20),
            ];
        });
    }
}
