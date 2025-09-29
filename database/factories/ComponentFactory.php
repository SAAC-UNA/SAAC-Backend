<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\Comment; // si tu modelo es Comentario, usa: use App\Models\Comentario as Comment;

class ComponentFactory extends Factory
{
    protected $model = Component::class;

    public function definition(): array
    {
        return [
            'dimension_id'  => Dimension::factory(),     // 👈 obligatorio en tu tabla
            'comentario_id' => Comment::factory(),       // si tu schema lo pide
            'nombre'        => 'Componente '.fake()->unique()->word(),
            'nomenclatura'  => strtoupper(fake()->bothify('COMP-##')), // si es unique, mejor unique()
        ];
    }

    // Para forzar una dimensión concreta desde un test:
    public function forDimension(Dimension $dimension): static
    {
        return $this->state(fn () => [
            'dimension_id' => $dimension->getKey(),
        ]);
    }
}
