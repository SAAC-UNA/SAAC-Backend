<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comment;
use App\Models\Dimension;
use App\Models\Component;
use App\Models\Criterion;
use App\Models\User;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea comentarios de ejemplo usando relación polimórfica.
     * Los comentarios pueden estar asociados a dimensiones, componentes, criterios, etc.
     */
    public function run(): void
    {
        // Obtener primer usuario del sistema (Superusuario)
        $usuario = User::first();

        if (!$usuario) {
            $this->command->warn('⚠️  No se encontraron usuarios. CommentSeeder se salta.');
            return;
        }

        // Comentarios en una dimensión
        $dimension = Dimension::first();
        if ($dimension) {
            $dimension->comments()->create([
                'usuario_id' => $usuario->usuario_id,
                'texto' => 'Esta dimensión necesita revisión de nomenclatura.'
            ]);
        }

        // Comentarios en un componente
        $component = Component::first();
        if ($component) {
            $component->comments()->createMany([
                [
                    'usuario_id' => $usuario->usuario_id,
                    'texto' => 'Componente bien estructurado.'
                ],
                [
                    'usuario_id' => $usuario->usuario_id,
                    'texto' => 'Pendiente validación SINAES.'
                ]
            ]);
        }

        // Comentarios en un criterio
        $criterion = Criterion::first();
        if ($criterion) {
            $criterion->comments()->create([
                'usuario_id' => $usuario->usuario_id,
                'texto' => 'Criterio aprobado para acreditación.'
            ]);
        }

        $this->command->info('✅ Comentarios polimórficos creados exitosamente');
    }
}
