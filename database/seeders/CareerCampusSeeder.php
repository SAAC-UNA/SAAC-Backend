<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CareerCampusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Vincula las carreras con el Campus Alajuela (Sede Regional Central Occidente)
     * Todas las 4 carreras se imparten en este campus
     */
    public function run(): void
    {
        // Obtener el Campus Alajuela
        $campusAlajuela = DB::table('SEDE')
            ->where('nombre', 'LIKE', '%Alajuela%')
            ->first();

        if (!$campusAlajuela) {
            $this->command->error('❌ No se encontró el Campus Alajuela');
            return;
        }

        // Obtener todas las carreras
        $carreras = DB::table('CARRERA')->get();

        $carreraSede = [];
        foreach ($carreras as $carrera) {
            $carreraSede[] = [
                'carrera_id' => $carrera->carrera_id,
                'sede_id' => $campusAlajuela->sede_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('CARRERA_SEDE')->insert($carreraSede);

        $this->command->info("✅ {$carreras->count()} carreras vinculadas al Campus Alajuela");
    }
}
