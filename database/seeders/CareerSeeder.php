<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Career;
use App\Models\Campus;

class CareerSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎓 Creando Carreras...');

        // Obtener la primera sede (Campus Central de la UNA)
        $campusCentral = Campus::where('nombre', 'LIKE', '%Alajuela%')->first();
        
        if (!$campusCentral) {
            $this->command->error('❌ No se encontró un campus. Crea sedes primero.');
            return;
        }

        $this->command->info("📍 Asignando carreras a: {$campusCentral->nombre}");

        $careerNames = [
            'Ingeniería en Sistemas de Información',
            'Química Industrial',
            'Administración de Empresas',
            'Inglés',
        ];

        foreach ($careerNames as $careerName) {
            // Crear o encontrar la carrera
            $career = Career::firstOrCreate(
                ['nombre' => $careerName],
                ['activo' => true]
            );

            // Asociar con el campus usando syncWithoutDetaching para evitar duplicados
            $career->campuses()->syncWithoutDetaching([$campusCentral->sede_id]);
            $this->command->info("✅ {$career->nombre} asociada a {$campusCentral->nombre}");
        }

        $this->command->info('🎉 Carreras creadas y asociadas');
    }
}
