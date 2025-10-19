<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DimensionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Pobla las 4 dimensiones del Modelo Evaluativo SINAES
     * NOTA: comentario_id es temporal (será refactorizado en próximo sprint)
     */
    public function run(): void
    {
        // Obtener primer comentario (temporal - relación será refactorizada)
        $comentario = DB::table('COMENTARIO')->first();
        
        if (!$comentario) {
            $this->command->error('❌ No se encontró ningún comentario. CommentSeeder debe ejecutarse primero.');
            return;
        }

        // Las 4 dimensiones principales del modelo SINAES
        $dimensiones = [
            [
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Relación con el contexto',
                'nomenclatura' => '1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Recursos',
                'nomenclatura' => '2',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Proceso educativo',
                'nomenclatura' => '3',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Resultados',
                'nomenclatura' => '4',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('DIMENSION')->insert($dimensiones);

        $this->command->info('✅ 4 dimensiones SINAES creadas exitosamente');
    }
}
