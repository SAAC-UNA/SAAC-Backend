<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => \App\Models\User::factory(),
            'texto' => $this->faker->sentence(8),
            // Los campos polimórficos commentable_type y commentable_id deben
            // ser establecidos manualmente cuando se cree el comentario usando for():
            // Comment::factory()->for($criterion)->create()
            // o especificados directamente:
            // Comment::factory()->create(['commentable_type' => Criterion::class, 'commentable_id' => $criterion->id])
            // Removido 'fecha_creacion' porque la tabla usa timestamps() de Laravel
            // que automáticamente crea created_at y updated_at
        ];
    }
}
