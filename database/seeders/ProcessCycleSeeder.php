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

        // Buscar administradores base
        $adminInge = User::where('email', 'jose.jara.arias@est.una.ac.cr')->first();
        $adminQuimi = User::where('email', 'alejandro.ugalde.villalobos@est.una.ac.cr')->first();

        if (!$adminInge || !$adminQuimi) {
            $this->command->error(' Faltan los usuarios administradores. Ejecuta primero UserRoleCareerSeeder.');
            return;
        }

        // Procesar cada carrera con su administrador
        Auth::setUser($adminInge);
        $this->crearCiclosYProcesos('Ingeniería en Sistemas');

        Auth::setUser($adminQuimi);
        $this->crearCiclosYProcesos('Química');

        $this->command->info(' Ciclos y procesos creados correctamente para cada carrera (según usuario autenticado).');
    }

    /**
     * Crea ciclo y proceso filtrado por carrera-sede
     */
    private function crearCiclosYProcesos(string $nombreCarrera): void
    {
        $nombreCarrera = trim($nombreCarrera);
        $this->command->info(" Creando datos para carrera: {$nombreCarrera}...");

        // Buscar carrera existente
        $career = Career::where('nombre', 'LIKE', "%{$nombreCarrera}%")->first();
        if (!$career) {
            $this->command->error(" No se encontró la carrera {$nombreCarrera}.");
            return;
        }

        // Buscar primera sede existente (o crear si no hay)
        $sede = Campus::first();
        if (!$sede) {
            $this->command->error(" No existe ninguna sede en la base de datos.");
            return;
        }

        // Buscar o crear relación carrera-sede
        $careerCampus = CareerCampus::where('carrera_id', $career->carrera_id)
            ->where('sede_id', $sede->sede_id)
            ->first();

        if (!$careerCampus) {
            $careerCampus = CareerCampus::create([
                'carrera_id' => $career->carrera_id,
                'sede_id' => $sede->sede_id,
            ]);
            $this->command->warn(" Se creó la relación carrera-sede para {$nombreCarrera}.");
        } else {
            $this->command->info("ℹYa existe la relación carrera-sede para {$nombreCarrera}.");
        }

        // Crear o buscar ciclo por carrera-sede
        $cycle = AccreditationCycle::where('carrera_sede_id', $careerCampus->carrera_sede_id)
            ->where('nombre', "Ciclo {$nombreCarrera} 2025-2030")
            ->first();

        if (!$cycle) {
            $cycle = AccreditationCycle::create([
                'carrera_sede_id' => $careerCampus->carrera_sede_id,
                'nombre' => "Ciclo {$nombreCarrera} 2025-2030",
            ]);
            $this->command->info(" Ciclo creado para {$nombreCarrera}.");
        } else {
            $this->command->warn(" Ciclo ya existente para {$nombreCarrera} en esta sede.");
        }

        // Crear o buscar proceso dentro del ciclo
        $process = Process::where('ciclo_acreditacion_id', $cycle->ciclo_acreditacion_id)
            ->where('tipo_proceso', 'Evaluación')
            ->first();

        if (!$process) {
            Process::create([
                'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
                'tipo_proceso' => 'Evaluación',
            ]);
            $this->command->info(" Proceso creado para {$nombreCarrera}.\n");
        } else {
            $this->command->warn(" Proceso ya existente para {$nombreCarrera} en este ciclo.\n");
        }
    }
}
