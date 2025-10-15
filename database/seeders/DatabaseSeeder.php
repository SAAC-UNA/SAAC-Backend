<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal que ejecuta los demás seeders del sistema.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta todos los seeders registrados en la aplicación.
     *
     * @return void
     */
    public function run(): void
    {
        
        $this->call([
        PermissionSeeder::class, // Seeder de permisos
        UserSeeder::class,       // Seeder de usuarios (lo que acabás de crear)
    ]);

    }
}
