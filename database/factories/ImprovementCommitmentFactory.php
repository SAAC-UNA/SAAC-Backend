<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Process;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImprovementCommitment>
 */
class ImprovementCommitmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fechaInicio = $this->faker->dateTimeBetween('now', '+1 month');
        $fechaFin = $this->faker->dateTimeBetween($fechaInicio, '+3 months');

        return [
            'proceso_id' => Process::factory(),
            'descripcion' => $this->faker->sentence(6),
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'estado' => $this->faker->randomElement(['Pendiente', 'En Progreso', 'Completado', 'Vencido']),
            'activo' => true,
        ];
    }

    /**
     * Estado Pendiente.
     */
    public function pendiente()
    {
        return $this->state(function (array $attributes) {
            return [
                'estado' => 'Pendiente',
            ];
        });
    }

    /**
     * Estado En Progreso.
     */
    public function enProgreso()
    {
        return $this->state(function (array $attributes) {
            return [
                'estado' => 'En Progreso',
            ];
        });
    }

    /**
     * Estado Completado.
     */
    public function completado()
    {
        return $this->state(function (array $attributes) {
            return [
                'estado' => 'Completado',
            ];
        });
    }

    /**
     * Estado Vencido.
     */
    public function vencido()
    {
        return $this->state(function (array $attributes) {
            return [
                'estado' => 'Vencido',
            ];
        });
    }

    /**
     * Compromiso activo.
     */
    public function activo()
    {
        return $this->state(function (array $attributes) {
            return [
                'activo' => true,
            ];
        });
    }

    /**
     * Compromiso inactivo.
     */
    public function inactivo()
    {
        return $this->state(function (array $attributes) {
            return [
                'activo' => false,
            ];
        });
    }
}
