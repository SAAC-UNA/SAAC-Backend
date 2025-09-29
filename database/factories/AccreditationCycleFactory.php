<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccreditationCycle>
 */
class AccreditationCycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'carrera_sede_id' => \App\Models\CareerCampus::factory(),
            'nombre' => $this->faker->words(2, true),
        ];
    }
}
