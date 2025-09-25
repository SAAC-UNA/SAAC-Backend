<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImprovementCommitment>
 */
class ImprovementCommitmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proceso_id' => \App\Models\Process::factory(),
            'fecha_inicio' => $this->faker->date(),
            'fecha_fin' => $this->faker->date(),
        ];
    }
}
