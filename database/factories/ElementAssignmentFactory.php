<?php

namespace Database\Factories;

use App\Models\ElementAssignment;
use App\Models\StructureElement;
use App\Models\User;
use App\Models\Process;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para generar datos de prueba de ElementAssignment (tabla ELEMENTO_ASIGNACION).
 *
 * Uso:
 * - ElementAssignment::factory()->create()
 * - ElementAssignment::factory()->pendiente()->create()
 * - ElementAssignment::factory()->create(['usuario_id' => $user->usuario_id])
 */
class ElementAssignmentFactory extends Factory
{
    protected $model = ElementAssignment::class;

    public function definition(): array
    {
        return [
            'elemento_id'  => StructureElement::factory(),
            'usuario_id'   => User::factory(),
            'proceso_id'   => Process::factory(),
            'asignado_por' => User::factory(),
            'estado'       => ElementAssignment::ESTADO_PENDIENTE,
            'fecha_limite' => $this->faker->dateTimeBetween('+7 days', '+60 days')->format('Y-m-d'),
            'comentario'   => null,
        ];
    }

    public function pendiente(): static
    {
        return $this->state(['estado' => ElementAssignment::ESTADO_PENDIENTE]);
    }

    public function enProgreso(): static
    {
        return $this->state(['estado' => ElementAssignment::ESTADO_EN_PROGRESO]);
    }

    public function completado(): static
    {
        return $this->state(['estado' => ElementAssignment::ESTADO_COMPLETADO]);
    }
}
