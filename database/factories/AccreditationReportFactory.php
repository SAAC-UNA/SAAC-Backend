<?php

namespace Database\Factories;

use App\Models\AccreditationCycle;
use App\Models\AccreditationReport;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccreditationReport>
 */
class AccreditationReportFactory extends Factory
{
    protected $model = AccreditationReport::class;

    public function definition(): array
    {
        return [
            'proceso_id'             => \App\Models\Process::factory(),
            'usuario_id'             => User::factory(),
            'usuario_publicacion_id' => User::factory(),
            'estado'                 => AccreditationReport::STATUS_PUBLISHED,
            'fecha_publicacion'      => now(),
            'observaciones'          => null,
            'fecha_subida'           => now(),
            'tipo'                   => 'archivo',
            'path'                   => 'reports/' . $this->faker->uuid() . '.pdf',
            'nombre_original'        => $this->faker->word() . '.pdf',
            'tamanio'                => $this->faker->numberBetween(1000, 5000000),
            'tipo_mime'              => 'application/pdf',
            'is_publico'             => true,
            'token_publico'          => $this->faker->uuid(),
        ];
    }

    /** Estado: publicado */
    public function published(): static
    {
        return $this->state(['estado' => AccreditationReport::STATUS_PUBLISHED]);
    }

    /** Estado: despublicado */
    public function unpublished(): static
    {
        return $this->state(['estado' => AccreditationReport::STATUS_UNPUBLISHED]);
    }
}
