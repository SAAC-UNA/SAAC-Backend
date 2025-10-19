<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campus;
use App\Models\University;

class CampusSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏢 Creando Campus...');

        $university = University::where('nombre', 'Universidad Nacional de Costa Rica')->first();
        
        if (!$university) {
            $this->command->error('❌ Universidad Nacional de Costa Rica no encontrada. Ejecuta UniversitySeeder primero.');
            return;
        }

        Campus::firstOrCreate(
            ['nombre' => 'Sección Regional Central Occidente - Campus Alajuela'],
            [
                'universidad_id' => $university->universidad_id,
                'nombre' => 'Sección Regional Central Occidente - Campus Alajuela',
                'activo' => true,
            ]
        );

        $this->command->info('✅ Campus Alajuela creado');
    }
}
