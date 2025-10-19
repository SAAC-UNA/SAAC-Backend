<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoevaluationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea autoevaluaciones para los procesos de tipo "Autoevaluación"
     */
    public function run(): void
    {
        // Obtener procesos de tipo Autoevaluación
        $procesosAutoevaluacion = DB::table('PROCESO')
            ->where('tipo_proceso', 'Autoevaluación')
            ->get();

        if ($procesosAutoevaluacion->isEmpty()) {
            $this->command->error('❌ No se encontraron procesos de tipo Autoevaluación');
            return;
        }

        $autoevaluaciones = [];

        foreach ($procesosAutoevaluacion as $proceso) {
            $autoevaluaciones[] = [
                'proceso_id' => $proceso->proceso_id,
                'fecha_inicio' => Carbon::now()->subMonths(6), // Iniciada hace 6 meses
                'fecha_fin' => Carbon::now()->addMonths(6),    // Finalizará en 6 meses
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('AUTOEVALUACION')->insert($autoevaluaciones);

        $this->command->info("✅ " . count($autoevaluaciones) . " autoevaluaciones creadas exitosamente");
    }
}
