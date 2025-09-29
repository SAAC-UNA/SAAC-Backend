<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => \App\Models\User::factory(),
            'tipo_accion_id' => \App\Models\ActionType::factory(),
            'detalle' => $this->faker->sentence(6),
            'fecha_hora' => $this->faker->dateTime(),
        ];
    }
}
