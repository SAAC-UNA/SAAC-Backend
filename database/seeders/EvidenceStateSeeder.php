<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvidenceStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Pobla los estados de evidencia del sistema SAAC
     */
    public function run(): void
    {
                $estados = [
            [
                'nombre' => 'Pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'En revisión',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Aprobada',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Rechazada',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('ESTADO_EVIDENCIA')->insert($estados);

        $this->command->info('✅ 4 estados de evidencia creados exitosamente');
    }
}
