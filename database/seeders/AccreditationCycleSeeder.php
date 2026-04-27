<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        $today = Carbon::today();

        // Los ciclos de prueba usan el modelo tradicional (SINAES 2018), insertado por migraciones/seeders.
        $primerModelo = DB::table('MODELO_ESTRUCTURA')
            ->where('tipo', 'tradicional')
            ->value('modelo_estructura_id');

        if (! $primerModelo) {
            $this->command->warn('⚠️  AccreditationCycleSeeder omitido: no existe el modelo tradicional.');
            $this->command->warn('   Ejecutá primero: php artisan db:seed --class=StructureModelSeeder');

            return;
        }

        $segundoModelo = DB::table('MODELO_ESTRUCTURA')
            ->where('tipo', 'elemento_flexible')
            ->value('modelo_estructura_id');

        if (! $segundoModelo) {
            $this->command->warn('⚠️  AccreditationCycleSeeder omitido: no existe el modelo flexible.');
            $this->command->warn('   Ejecutá primero: php artisan db:seed --class=StructureModelSeeder');

            return;
        }

        // Limpieza total solicitada para regenerar ciclos/procesos con el esquema actual.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('PROCESO')->truncate();
        DB::table('CICLO_ACREDITACION')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

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

        // Crear ciclos por carrera/sede con fecha_inicio/fecha_fin y estado derivado por fechas.
        foreach ($carrerasSede as $carreraSede) {
            // Ciclo histórico (completado por fecha).
            $historicalStart = $today->copy()->subYears(5)->startOfYear();
            $historicalEnd = $today->copy()->subYear()->endOfYear();
            $ciclos[] = [
                'carrera_sede_id' => $carreraSede->carrera_sede_id,
                'nombre' => $this->buildDynamicName($historicalStart, $historicalEnd),
                'modelo_estructura_id' => $primerModelo,
                'fecha_inicio' => (string) $historicalStart->year,
                'fecha_fin' => (string) $historicalEnd->year,
                'estado' => $this->resolveStatusByDates($historicalStart, $historicalEnd, $today),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Ciclo vigente (activo por fecha).
            $currentStart = $today->copy()->startOfYear();
            $currentEnd = $today->copy()->addYears(4)->endOfYear();
            $ciclos[] = [
                'carrera_sede_id' => $carreraSede->carrera_sede_id,
                'nombre' => $this->buildDynamicName($currentStart, $currentEnd),
                'modelo_estructura_id' => $segundoModelo,
                'fecha_inicio' => (string) $currentStart->year,
                'fecha_fin' => (string) $currentEnd->year,
                'estado' => $this->resolveStatusByDates($currentStart, $currentEnd, $today),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('CICLO_ACREDITACION')->insert($ciclos);

        $this->command->info('✅ Limpieza completa aplicada en PROCESO y CICLO_ACREDITACION');
        $this->command->info('✅ '.count($ciclos).' ciclos de acreditación creados exitosamente');
        $this->command->info("   ({$carrerasSede->count()} relaciones carrera-sede × 2 ciclos)");
    }

    private function buildDynamicName(Carbon $startDate, Carbon $endDate): string
    {
        $startYear = $startDate->year;
        $endYear = $endDate->year;

        if ($startYear === $endYear) {
            return "Ciclo {$startYear}";
        }

        return "Ciclo {$startYear}-{$endYear}";
    }

    private function resolveStatusByDates(Carbon $startDate, Carbon $endDate, Carbon $today): string
    {
        if ($today->lt($startDate)) {
            return 'inactivo';
        }

        if ($today->gt($endDate)) {
            return 'completado';
        }

        return 'activo';
    }
}
