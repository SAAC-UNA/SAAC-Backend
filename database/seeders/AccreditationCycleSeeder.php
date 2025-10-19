<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccreditationCycleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea ciclos de acreditación para las carreras del Campus Alajuela
     * Ejemplo: Ciclo 2024-2028, Ciclo 2025-2029
     */
    public function run(): void
    {
        // Obtener todas las relaciones carrera-sede
        $carrerasSede = DB::table('CARRERA_SEDE')
            ->join('CARRERA', 'CARRERA_SEDE.carrera_id', '=', 'CARRERA.carrera_id')
            ->join('SEDE', 'CARRERA_SEDE.sede_id', '=', 'SEDE.sede_id')
            ->select('CARRERA_SEDE.carrera_sede_id', 'CARRERA.nombre as carrera_nombre', 'SEDE.nombre as sede_nombre')
            ->get();

        if ($carrerasSede->isEmpty()) {
            $this->command->error('❌ No se encontraron carreras vinculadas a sedes');
            return;
        }

        $ciclos = [];
        
        // Crear ciclos de acreditación para cada carrera
        foreach ($carrerasSede as $carreraSede) {
            // Ciclo actual 2024-2028
            $ciclos[] = [
                'carrera_sede_id' => $carreraSede->carrera_sede_id,
                'nombre' => 'Ciclo de Acreditación 2024-2028',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Ciclo próximo 2025-2029
            $ciclos[] = [
                'carrera_sede_id' => $carreraSede->carrera_sede_id,
                'nombre' => 'Ciclo de Acreditación 2025-2029',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('CICLO_ACREDITACION')->insert($ciclos);

        $this->command->info("✅ " . count($ciclos) . " ciclos de acreditación creados exitosamente");
        $this->command->info("   ({$carrerasSede->count()} carreras × 2 ciclos cada una)");
    }
}
