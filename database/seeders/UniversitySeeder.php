<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\University;

class UniversitySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏛️  Creando Universidad...');

        University::firstOrCreate(
            ['nombre' => 'Universidad Nacional de Costa Rica'],
            [
                'nombre' => 'Universidad Nacional de Costa Rica',
                'activo' => true,
            ]
        );

        $this->command->info('✅ Universidad Nacional de Costa Rica creada');
    }
}
