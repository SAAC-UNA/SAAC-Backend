<?php

namespace Database\Factories;

use App\Models\StructureModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StructureModel>
 */
class StructureModelFactory extends Factory
{
    protected $model = StructureModel::class;

    public function definition(): array
    {
        return [
            'nombre'      => $this->faker->unique()->words(3, true),
            'descripcion' => $this->faker->sentence(),
            'tipo'        => StructureModel::TIPO_ELEMENTO_FLEXIBLE,
            'version'     => $this->faker->year(),
            'activo'      => true,
        ];
    }

    /**
     * Estado para modelo tradicional (p.ej. tests de evidencias).
     */
    public function tradicional(): static
    {
        return $this->state(['tipo' => StructureModel::TIPO_TRADICIONAL]);
    }

    /**
     * Estado para modelo flexible (default — tests de elementos).
     */
    public function flexible(): static
    {
        return $this->state(['tipo' => StructureModel::TIPO_ELEMENTO_FLEXIBLE]);
    }
}
