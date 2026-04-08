<?php

namespace Database\Factories;

use App\Models\EvidenceAssignment;
use App\Models\Process;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvidenceAssignmentFactory extends Factory
{
    protected $model = EvidenceAssignment::class;

    public function definition()
    {
        return [
            'proceso_id' => Process::factory(),
            'evidencia_id' => Evidence::factory(),
            'usuario_id' => User::factory(),
            'estado' => $this->faker->randomElement(['Pendiente', 'En Progreso', 'Completado', 'Vencido']),
            'fecha_asignacion' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'fecha_limite' => $this->faker->dateTimeBetween('now', '+1 month'),
            'comentario' => $this->faker->optional(0.7)->sentence(12),
        ];
    }
}
