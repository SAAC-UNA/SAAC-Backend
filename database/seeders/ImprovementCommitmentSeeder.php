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
        $index = 1;

        foreach ($procesosCompromiso as $proceso) {
            $compromisos[] = [
                'proceso_id' => $proceso->proceso_id,
                'entidad_tipo' => 'CRITERIO', // Ejemplo: asociado a un criterio
                'entidad_id' => $index, // ID del criterio (1, 2, 3, 4...)
                'descripcion' => "Compromiso de mejora para el ciclo {$proceso->ciclo_acreditacion_id}",
                'fecha_inicio' => Carbon::now()->subMonths(3), // Iniciado hace 3 meses
                'fecha_fin' => Carbon::now()->addMonths(9),    // Finalizará en 9 meses
                'estado' => 'Pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $index++;
        }

        DB::table('COMPROMISO_MEJORA')->insert($compromisos);

        $this->command->info("✅ " . count($compromisos) . " compromisos de mejora creados exitosamente");
    }
}
