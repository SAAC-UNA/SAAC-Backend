<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * NOTA: Este seeder es temporal. La relación comentario_id en varias tablas
     * (DIMENSION, COMPONENTE, CRITERIO) está marcada como nullable y será
     * refactorizada en el próximo sprint. Por ahora creamos algunos comentarios
     * de ejemplo para pruebas.
     */
    public function run(): void
    {
        // Obtener primer usuario del sistema (Superusuario)
        $usuario = DB::table('USUARIO')->first();

        if (!$usuario) {
            $this->command->warn('⚠️  No se encontraron usuarios. CommentSeeder se salta.');
            return;
        }

        $comentarios = [
            [
                'usuario_id' => $usuario->usuario_id,
                'texto' => 'Comentario de ejemplo para pruebas del sistema.',
                'fecha_creacion' => Carbon::now()->subDays(10),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuario->usuario_id,
                'texto' => 'Observación temporal sobre proceso de autoevaluación.',
                'fecha_creacion' => Carbon::now()->subDays(5),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuario->usuario_id,
                'texto' => 'Nota para revisión posterior del criterio.',
                'fecha_creacion' => Carbon::now()->subDays(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('COMENTARIO')->insert($comentarios);

        $this->command->info('✅ 3 comentarios de ejemplo creados (seeder temporal)');
    }
}
