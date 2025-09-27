<?php

namespace Database\Factories;

<<<<<<< HEAD
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campus>
 */
class CampusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'universidad_id' => \App\Models\University::factory(),
=======
use App\Models\Campus;
use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampusFactory extends Factory
{
    protected $model = Campus::class;

    public function definition(): array
    {
        return [
            'universidad_id' => University::factory(), // se asegura de tener FK válida
>>>>>>> 02_API_de_Endpoints_de_Estructura
            'nombre' => $this->faker->city . ' Campus',
        ];
    }
}
