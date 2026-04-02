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
            ->get();

        if ($procesosCompromiso->isEmpty()) {
            $this->command->error('❌ No se encontraron procesos de tipo Compromiso de mejora');
            return;
        }

        $compromisos = [];
        $index = 0;

        foreach ($procesosCompromiso as $proceso) {
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

        // Vincular evidencias a cada compromiso (tabla pivote COMPROMISO_MEJORA_EVIDENCIA)
        $compromisosCreados = DB::table('COMPROMISO_MEJORA')
            ->whereIn('proceso_id', $procesosCompromiso->pluck('proceso_id'))
            ->get();

        // Obtener IDs reales de evidencias disponibles en la DB
        $todosLosIds = DB::table('EVIDENCIA')->pluck('evidencia_id')->toArray();

        if (empty($todosLosIds)) {
            $this->command->warn('⚠️  No hay evidencias en la DB, se omite la vinculación');
            $this->command->info("✅ " . count($compromisos) . " compromisos de mejora creados (sin evidencias vinculadas)");
            return;
        }

        // Dividir los IDs disponibles en 4 grupos para rotar entre compromisos
        $chunks = array_chunk($todosLosIds, max(1, (int) ceil(count($todosLosIds) / 4)));
        $grupos = [
            0 => array_slice($chunks[0] ?? [], 0, 3),
            1 => array_slice($chunks[1] ?? $chunks[0], 0, 2),
            2 => array_slice($chunks[2] ?? $chunks[0], 0, 3),
            3 => array_slice($chunks[3] ?? $chunks[0], 0, 3),
        ];

        $evidenciasPivot = [];
        foreach ($compromisosCreados as $compromiso) {
            $grupo = $compromiso->compromiso_mejora_id % 4;
            $evidenciasIds = $grupos[$grupo];

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
