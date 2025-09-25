<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Criterion>
 */
class CriterionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'componente_id' => \App\Models\Component::factory(),
            'comentario_id' => \App\Models\Comment::factory(),
            'descripcion' => $this->faker->sentence(6),
            'nomenclatura' => $this->faker->bothify('CRIT-##'),
        ];
    }
}
