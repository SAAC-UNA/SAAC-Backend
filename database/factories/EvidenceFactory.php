<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Evidence;
use App\Models\Criterion;
use App\Models\EvidenceState;

class EvidenceFactory extends Factory
{
    protected $model = Evidence::class;

    public function definition(): array
    {
        return [
            'criterio_id'         => Criterion::factory(),
            'estado_evidencia_id' => EvidenceState::factory(),
            'descripcion'         => $this->faker->sentence(6),
            'nomenclatura'        => strtoupper($this->faker->bothify('EVID-##')),
        ];
    }
}