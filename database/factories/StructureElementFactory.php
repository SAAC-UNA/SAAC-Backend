<?php

namespace Database\Factories;

use App\Models\StructureElement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para generar datos de prueba de StructureElement (tabla ELEMENTO).
 *
 * Uso:
 * - StructureElement::factory()->create()
 * - StructureElement::factory()->pendiente()->create()
 * - StructureElement::factory()->create(['descripcion' => 'Plan Estratégico'])
 */
class StructureElementFactory extends Factory
{
    protected $model = StructureElement::class;

    public function definition(): array
    {
        return [
            'modelo_estructura_id' => \App\Models\StructureModel::factory(),
            'padre_id'             => null,
            'tipo'                 => $this->faker->randomElement(['criterio', 'componente', 'estandar']),
            'categoria'            => $this->faker->randomElement(['A', 'B', 'C', null]),
            'nomenclatura'         => strtoupper($this->faker->lexify('??-###')),
            'descripcion'          => $this->faker->sentence(6),
            'activo'               => true,
            'estado'               => 'Pendiente',
            'fecha_limite'         => $this->faker->dateTimeBetween('+7 days', '+60 days')->format('Y-m-d'),
        ];
    }

    /** Estado Pendiente */
    public function pendiente(): static
    {
        return $this->state(['estado' => 'Pendiente']);
    }

    /** Estado En Progreso */
    public function enProgreso(): static
    {
        return $this->state(['estado' => 'En Progreso']);
    }

    /** Estado Completado */
    public function completado(): static
    {
        return $this->state(['estado' => 'Completado']);
    }
}
