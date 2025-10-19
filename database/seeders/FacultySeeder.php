<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faculty;
use App\Models\Campus;
use App\Models\University;

class FacultySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏛️  Creando Facultades...');

        $university = University::where('nombre', 'Universidad Nacional de Costa Rica')->first();
        $campusAlajuela = Campus::where('nombre', 'LIKE', '%Alajuela%')->first();
        
        if (!$university) {
            $this->command->error('❌ Universidad Nacional no encontrada. Ejecuta UniversitySeeder primero.');
            return;
        }
        
        if (!$campusAlajuela) {
            $this->command->error('❌ Campus Alajuela no encontrado. Ejecuta CampusSeeder primero.');
            return;
        }

        $faculties = [
            [
                'universidad_id' => $university->universidad_id,
                'sede_id' => $campusAlajuela->sede_id,
                'nombre' => 'Facultad de Ciencias Exactas y Naturales',
                'activo' => true,
            ],
            [
                'universidad_id' => $university->universidad_id,
                'sede_id' => $campusAlajuela->sede_id,
                'nombre' => 'Facultad de Ciencias Sociales',
                'activo' => true,
            ],
            [
                'universidad_id' => $university->universidad_id,
                'sede_id' => $campusAlajuela->sede_id,
                'nombre' => 'Facultad de Filosofía y Letras',
                'activo' => true,
            ],
        ];

        foreach ($faculties as $faculty) {
            $created = Faculty::firstOrCreate(
                [
                    'nombre' => $faculty['nombre'], 
                    'universidad_id' => $faculty['universidad_id'],
                    'sede_id' => $faculty['sede_id']
                ],
                $faculty
            );

            $this->command->info("✅ {$created->nombre}");
        }

        $this->command->info('🎉 Facultades creadas');
    }
}

