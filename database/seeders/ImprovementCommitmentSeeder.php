<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImprovementCommitmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea compromisos de mejora para los procesos de tipo "Compromiso de mejora"
     */
    public function run(): void
    {
        // Obtener procesos de tipo Compromiso de mejora
        $procesosCompromiso = DB::table('PROCESO')
            ->where('tipo_proceso', 'Compromiso de mejora')
            ->orderBy('proceso_id')
            ->get();

        if ($procesosCompromiso->isEmpty()) {
            $this->command->error('❌ No se encontraron procesos de tipo Compromiso de mejora');
            return;
        }

        // Dejar el último proceso libre para pruebas manuales en Postman
        $procesosParaSeed = $procesosCompromiso->take($procesosCompromiso->count() - 1);
        $procesoLibre = $procesosCompromiso->last();

        $compromisos = [];
        $index = 0;

        foreach ($procesosParaSeed as $proceso) {
            $compromisos[] = [
                'proceso_id' => $proceso->proceso_id,
                'descripcion' => "Compromiso de mejora para el ciclo {$proceso->ciclo_acreditacion_id}",
                'fecha_inicio' => Carbon::now()->subMonths(3),
                'fecha_fin' => Carbon::now()->addMonths(9),
                'estado' => 'Pendiente',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $index++;
        }

        DB::table('COMPROMISO_MEJORA')->insert($compromisos);

        $this->command->info("⚠️  Proceso LIBRE para testing: proceso_id={$procesoLibre->proceso_id} (tipo: {$procesoLibre->tipo_proceso})");

        // Vincular evidencias a cada compromiso (tabla pivote COMPROMISO_MEJORA_EVIDENCIA)
        $compromisosCreados = DB::table('COMPROMISO_MEJORA')
            ->whereIn('proceso_id', $procesosParaSeed->pluck('proceso_id'))
            ->get();

        $evidenciasPivot = [];
        foreach ($compromisosCreados as $compromiso) {
            // Asignar diferentes evidencias según el compromiso
            $evidenciasIds = match($compromiso->compromiso_mejora_id % 4) {
                1 => [1, 2, 3],      // Compromiso 1: evidencias 20, 21, 22
                2 => [4, 5],         // Compromiso 2: evidencias 23, 24
                3 => [10, 11, 12],   // Compromiso 3: evidencias 40, 41, 42
                0 => [7, 8, 9],      // Compromiso 4: evidencias 26, 27, 28
            };

            foreach ($evidenciasIds as $evidenciaId) {
                $evidenciasPivot[] = [
                    'compromiso_mejora_id' => $compromiso->compromiso_mejora_id,
                    'evidencia_id' => $evidenciaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('COMPROMISO_MEJORA_EVIDENCIA')->insert($evidenciasPivot);

        $this->command->info("✅ " . count($compromisos) . " compromisos de mejora creados con evidencias");
    }
}
