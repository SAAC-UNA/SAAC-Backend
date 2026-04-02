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
               /**
             * Relación con el modelo CareerCampus.
             * 
             * Cada ciclo de acreditación se asocia automáticamente con
             * un registro falso generado por la factory de CareerCampus.
             * Esto garantiza la integridad referencial de la relación
             * `carrera_sede_id`.
             */
            'carrera_sede_id'      => \App\Models\CareerCampus::factory(),
            'modelo_estructura_id' => \App\Models\StructureModel::factory(),
            'nombre'               => $this->faker->words(2, true),
        ];
    }
}
