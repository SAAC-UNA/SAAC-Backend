<?php

namespace Database\Factories;

use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

class UniversityFactory extends Factory
{
    protected $model = University::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->company(), 
            // Si luego haces NOT NULL otros campos, añádelos aquí:
            // 'sigla'       => strtoupper($this->faker->lexify('U??')),
            // 'descripcion' => $this->faker->sentence(8),
        ];
    }
}
