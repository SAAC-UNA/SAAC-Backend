<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Evidence;
use App\Models\Criterion;

class EvidenceFactory extends Factory
{
    protected $model = Evidence::class;

    public function definition(): array
    {
        return [
            'criterio_id'  => Criterion::factory(),
            'estado'       => 'Pendiente',
            'descripcion'  => $this->faker->text(70),
            'nomenclatura' => strtoupper($this->faker->bothify('EVID-##')),
        ];
    }
}
