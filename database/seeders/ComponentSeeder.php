<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Pobla componentes representativos del Modelo Evaluativo SINAES
     * Muestra de 2-3 componentes por dimensión para pruebas
     */
    public function run(): void
    {
        // Obtener primer comentario (temporal - relación será refactorizada)
        $comentario = DB::table('COMENTARIO')->first();
        
        if (!$comentario) {
            $this->command->error('❌ No se encontró ningún comentario. CommentSeeder debe ejecutarse primero.');
            return;
        }

        // Obtener IDs de las dimensiones
        $dimension1 = DB::table('DIMENSION')->where('nomenclatura', '1')->first();
        $dimension2 = DB::table('DIMENSION')->where('nomenclatura', '2')->first();
        $dimension3 = DB::table('DIMENSION')->where('nomenclatura', '3')->first();
        $dimension4 = DB::table('DIMENSION')->where('nomenclatura', '4')->first();

        $componentes = [
            // DIMENSIÓN 1: Relación con el contexto
            [
                'dimension_id' => $dimension1->dimension_id,
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Información y promoción',
                'nomenclatura' => '1.1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'dimension_id' => $dimension1->dimension_id,
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Proceso de admisión e ingreso',
                'nomenclatura' => '1.2',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'dimension_id' => $dimension1->dimension_id,
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Correspondencia con el contexto',
                'nomenclatura' => '1.3',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // DIMENSIÓN 2: Recursos
            [
                'dimension_id' => $dimension2->dimension_id,
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Plan de estudios',
                'nomenclatura' => '2.1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'dimension_id' => $dimension2->dimension_id,
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Personal académico',
                'nomenclatura' => '2.2',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'dimension_id' => $dimension2->dimension_id,
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Infraestructura',
                'nomenclatura' => '2.4',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // DIMENSIÓN 3: Proceso educativo
            [
                'dimension_id' => $dimension3->dimension_id,
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Desarrollo docente',
                'nomenclatura' => '3.1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'dimension_id' => $dimension3->dimension_id,
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Metodología enseñanza-aprendizaje',
                'nomenclatura' => '3.2',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'dimension_id' => $dimension3->dimension_id,
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Gestión de la carrera',
                'nomenclatura' => '3.3',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // DIMENSIÓN 4: Resultados
            [
                'dimension_id' => $dimension4->dimension_id,
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Desempeño estudiantil',
                'nomenclatura' => '4.1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'dimension_id' => $dimension4->dimension_id,
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Graduados',
                'nomenclatura' => '4.2',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'dimension_id' => $dimension4->dimension_id,
                'comentario_id' => $comentario->comentario_id,
                'nombre' => 'Proyección de la carrera',
                'nomenclatura' => '4.3',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('COMPONENTE')->insert($componentes);

        $this->command->info('✅ 12 componentes SINAES creados exitosamente (muestra representativa)');
    }
}
