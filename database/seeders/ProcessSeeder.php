<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea procesos de acreditación para los ciclos
     * Tipos de proceso: Autoevaluación, Compromiso de mejora
     */
    public function run(): void
    {
        // Obtener ciclos de acreditación activos (los del 2024-2028)
        $ciclos = DB::table('CICLO_ACREDITACION')
            ->where('nombre', 'LIKE', '%2024-2028%')
            ->get();

        if ($ciclos->isEmpty()) {
            $this->command->error('❌ No se encontraron ciclos de acreditación');
            return;
        }

        $procesos = [];

        // Tipos de procesos según el sistema SAAC
        // Cada ciclo tiene un proceso de Autoevaluación y uno de Compromiso de mejora
        foreach ($ciclos as $ciclo) {
            // Proceso de Autoevaluación
            $procesos[] = [
                'ciclo_acreditacion_id' => $ciclo->ciclo_acreditacion_id,
                'tipo_proceso' => 'Autoevaluación',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Proceso de Compromiso de mejora
            $procesos[] = [
                'ciclo_acreditacion_id' => $ciclo->ciclo_acreditacion_id,
                'tipo_proceso' => 'Compromiso de mejora',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('PROCESO')->insert($procesos);

        $this->command->info("✅ " . count($procesos) . " procesos creados exitosamente");
        $this->command->info("   ({$ciclos->count()} ciclos × 2 tipos de proceso)");
    }
}
