<?php

namespace Database\Factories;

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
            'nombre' => $this->faker->city . ' Campus',
            'activo' => true, // valor por defecto
        ];
    }
}
