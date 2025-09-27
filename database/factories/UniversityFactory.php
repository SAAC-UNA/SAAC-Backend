<?php

namespace Database\Factories;

<<<<<<< HEAD
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\university>
 */
class UniversityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company . ' Universidad',
=======
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
>>>>>>> 02_API_de_Endpoints_de_Estructura
        ];
    }
}
