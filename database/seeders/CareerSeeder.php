<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Career;
use App\Models\Campus;
use App\Models\CareerCampus;

class CareerSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎓 Creando Carreras...');

        // Obtener el campus de referencia para crear la entrada CARRERA_SEDE
        $campusCentral = Campus::where('nombre', 'LIKE', '%Alajuela%')->first();

        if (!$campusCentral) {
            $this->command->error('❌ No se encontró un campus. Crea sedes primero.');
            return;
        }

        $this->command->info("📍 Asignando carreras a: {$campusCentral->university?->nombre} – {$campusCentral->nombre}");

        $careerNames = [
            'Ingeniería en Sistemas de Información',
            'Química Industrial',
            'Administración de Empresas',
            'Inglés',
        ];

        foreach ($careerNames as $careerName) {
            // Crear o encontrar la carrera (ahora con universidad_id directo)
            $career = Career::firstOrCreate(
                ['nombre' => $careerName],
                [
                    'activo'         => true,
                    'universidad_id' => $campusCentral->universidad_id,
                ]
            );

            // Crear entrada en CARRERA_SEDE si no existe
            CareerCampus::firstOrCreate([
                'carrera_id' => $career->carrera_id,
                'sede_id'    => $campusCentral->sede_id,
            ]);

            $this->command->info("✅ {$career->nombre} → {$campusCentral->nombre}");
        }

        $this->command->info('🎉 Carreras creadas y asociadas');
    }
}
