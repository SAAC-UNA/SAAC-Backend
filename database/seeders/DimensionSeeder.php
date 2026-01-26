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
     */
    public function run(): void
    {
        // Las 4 dimensiones principales del modelo SINAES
        $dimensiones = [
            [
                'nombre' => 'Relación con el contexto',
                'nomenclatura' => '1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Recursos',
                'nomenclatura' => '2',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Proceso educativo',
                'nomenclatura' => '3',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
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
