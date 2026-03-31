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
        // Limpieza explícita para permitir re-ejecución de este seeder de forma aislada.
        DB::table('PROCESO')->delete();

        // Obtener todos los ciclos para generar procesos coherentes por estado.
        $ciclos = DB::table('CICLO_ACREDITACION')
            ->select('ciclo_acreditacion_id', 'estado')
            ->get();

        if ($ciclos->isEmpty()) {
            $this->command->error('❌ No se encontraron ciclos de acreditación');
            return;
        }

        $procesos = [];

        // Cada ciclo tiene dos procesos: Autoevaluación y Compromiso de mejora.
        foreach ($ciclos as $ciclo) {
            $esActivo = $ciclo->estado === 'activo';

            // Fechas de referencia por estado del ciclo.
            $inicioAutoevaluacion = $esActivo
                ? now()->startOfYear()->format('Y-m-d')
                : now()->subYears(2)->startOfYear()->format('Y-m-d');

            $finAutoevaluacion = $esActivo
                ? now()->addMonths(8)->format('Y-m-d')
                : now()->subYear()->endOfYear()->format('Y-m-d');

            $inicioCompromiso = $esActivo
                ? now()->addMonths(1)->format('Y-m-d')
                : now()->subYear()->startOfYear()->format('Y-m-d');

            $finCompromiso = $esActivo
                ? now()->addYear()->format('Y-m-d')
                : now()->subMonths(2)->format('Y-m-d');

            $procesos[] = [
                'ciclo_acreditacion_id' => $ciclo->ciclo_acreditacion_id,
                'tipo_proceso'          => 'Autoevaluación',
                'fecha_inicio'          => $inicioAutoevaluacion,
                'fecha_finalizacion'    => $finAutoevaluacion,
                'activo'                => $esActivo,
                'created_at'            => now(),
                'updated_at'            => now(),
            ];

            $procesos[] = [
                'ciclo_acreditacion_id' => $ciclo->ciclo_acreditacion_id,
                'tipo_proceso'          => 'Compromiso de mejora',
                'fecha_inicio'          => $inicioCompromiso,
                'fecha_finalizacion'    => $finCompromiso,
                'activo'                => false,
                'created_at'            => now(),
                'updated_at'            => now(),
            ];
        }

        DB::table('PROCESO')->insert($procesos);

        $this->command->info("✅ " . count($procesos) . " procesos creados exitosamente");
        $this->command->info("   ({$ciclos->count()} ciclos × 2 tipos de proceso)");
    }
}
