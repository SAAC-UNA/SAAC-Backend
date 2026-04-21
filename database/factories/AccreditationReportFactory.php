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
        $desde = $this->faker->dateTimeBetween('-2 years', '-1 year');
        $hasta = $this->faker->dateTimeBetween('+1 year', '+5 years');

        return [
            'ciclo_acreditacion_id'  => AccreditationCycle::factory(),
            'archivo_id'             => File::factory(),
            'usuario_publicacion_id' => User::factory(),
            'estado'                 => AccreditationReport::STATUS_PUBLISHED,
            'numero_resolucion'      => 'RES-' . $this->faker->unique()->numerify('####-####'),
            'fecha_resolucion'       => $desde->format('Y-m-d'),
            'vigencia_desde'         => $desde->format('Y-m-d'),
            'vigencia_hasta'         => $hasta->format('Y-m-d'),
            'fecha_publicacion'      => now(),
            'observaciones'          => null,
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
