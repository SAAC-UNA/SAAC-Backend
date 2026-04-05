<?php

namespace Database\Factories;

use App\Models\ElementApproval;
use App\Models\Process;
use App\Models\StructureElement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElementApprovalFactory extends Factory
{
    protected $model = ElementApproval::class;

    public function definition(): array
    {
        return [
            'elemento_id'       => StructureElement::factory(),
            'proceso_id'        => Process::factory(),
            'usuario_id'        => User::factory(),
            'estado'            => 'aprobado',
            'comentario'        => $this->faker->optional()->sentence(),
            'nueva_fecha_limite'=> null,
        ];
    }

    public function rechazado(): static
    {
        return $this->state(fn () => [
            'estado'            => 'rechazado',
            'nueva_fecha_limite'=> now()->addDays(30)->toDateString(),
        ]);
    }

    public function pendiente(): static
    {
        return $this->state(fn () => [
            'estado' => 'pendiente',
        ]);
    }
}
