<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActionType>
 */
class ActionTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Column TIPO_ACCION.descripcion is VARCHAR(50).
            'descripcion' => substr($this->faker->sentence(3, false), 0, 50),
        ];
    }
}
