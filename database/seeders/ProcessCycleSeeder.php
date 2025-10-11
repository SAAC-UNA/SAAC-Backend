<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Career;
use App\Models\Campus;
use App\Models\CareerCampus;
use App\Models\AccreditationCycle;
use App\Models\Process;

class ProcessCycleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando seeder de ciclos y procesos por carrera (según usuario administrador)...');

        /** 
         *  1️ Buscar administradores por carrera
          */
        $adminInge = User::where('email', 'cristopher.montero.jimenez@una.ac.cr')->first(); 
        $adminEdu  = User::where('email', 'ian.villegas.jimenez@est.una.ac.cr')->first();

        if (!$adminInge || !$adminEdu) {
            $this->command->error(' Faltan los usuarios administradores. Ejecuta primero UserRoleCareerSeeder.');
            return;
        }

        /** 
         *  2️ Procesar Ingeniería (como administrador.inge)
         **/
        Auth::setUser($adminInge);
        $this->crearCiclosYProcesos('Ingeniería');

        /** 
         *  3️ Procesar Educación (como administrador.edu)
         *  */
        Auth::setUser($adminEdu);
        $this->crearCiclosYProcesos('Educación');

        /** 
         *   Confirmación final
         *  */
        $this->command->info(' Ciclos y procesos creados correctamente para cada carrera (según usuario autenticado).');
    }

    /**
     *  Crea ciclo y proceso filtrado por carrera
     */
    private function crearCiclosYProcesos(string $nombreCarrera): void
    {
        $this->command->info(" Creando datos para carrera: {$nombreCarrera}...");

        // Buscar carrera existente
        $career = Career::where('nombre', 'LIKE', "%{$nombreCarrera}%")->first();

        if (!$career) {
            $this->command->error(" No se encontró la carrera {$nombreCarrera}.");
            return;
        }

        /** 
         *  Si no existe relación carrera-sede, crearla automáticamente
         **/
        $careerCampus = CareerCampus::where('carrera_id', $career->carrera_id)->first();

        if (!$careerCampus) {
            // Buscar la primera sede disponible
            $sede = Campus::first();
            if (!$sede) {
                $this->command->error("No existe ninguna sede en la base de datos.");
                return;
            }

            $careerCampus = CareerCampus::create([
                'carrera_id' => $career->carrera_id,
                'sede_id' => $sede->sede_id,
            ]);

            $this->command->warn(" Se creó automáticamente la relación carrera-sede para {$nombreCarrera}.");
        }

        /** 
         *  Crear ciclo de acreditación
         */
        $cycle = AccreditationCycle::firstOrCreate([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
            'nombre' => "Ciclo {$nombreCarrera} 2025-2030",
        ]);

        /** 
         *  Crear proceso asociado
         **/
        Process::firstOrCreate([
            'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
            'tipo_proceso' => 'Evaluación',
        ]);

        $this->command->info(" Ciclo y proceso creados para {$nombreCarrera}.\n");
    }
}
