<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Evidence>
 */
class EvidenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'criterio_id' => \App\Models\Criterion::factory(),
            'estado_evidencia_id' => \App\Models\EvidenceState::factory(),
            'descripcion' => $this->faker->sentence(6),
            'nomenclatura' => $this->faker->bothify('EVID-##'),
        ];
    }
}
