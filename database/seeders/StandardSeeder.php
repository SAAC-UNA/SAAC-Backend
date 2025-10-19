<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StandardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Pobla estándares representativos del Modelo Evaluativo SINAES
     * Los estándares no tienen nomenclatura, están vinculados a criterios específicos
     */
    public function run(): void
    {
        // Obtener criterios que tienen estándares
        $criterio1_1_1 = DB::table('CRITERIO')->where('nomenclatura', '1.1.1')->first();
        $criterio1_1_2 = DB::table('CRITERIO')->where('nomenclatura', '1.1.2')->first();
        $criterio2_1_1 = DB::table('CRITERIO')->where('nomenclatura', '2.1.1')->first();

        $estandares = [
            // Estándar para criterio 1.1.1
            [
                'criterio_id' => $criterio1_1_1->criterio_id,
                'descripcion' => 'La carrera debe contar al menos con un material informativo.',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Estándar para criterio 1.1.2
            [
                'criterio_id' => $criterio1_1_2->criterio_id,
                'descripcion' => 'Al menos un 70% de los estudiantes debe reportar que recibe la información necesaria para su vida académica.',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Estándar para criterio 2.1.1 (ejemplo de criterio con múltiples estándares)
            [
                'criterio_id' => $criterio2_1_1->criterio_id,
                'descripcion' => 'Todos los cursos -el 100%- deben contar con sus respectivos programas y éstos deben estar completos.',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('ESTANDAR')->insert($estandares);

        $this->command->info('✅ 3 estándares SINAES creados exitosamente (muestra representativa)');
    }
}
