<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal que ejecuta los demás seeders del sistema.
 *
 * Este seeder centraliza la ejecución de los seeders individuales.
 * Desde aquí se pueden agregar más seeders conforme se amplíen
 * las entidades del sistema (roles, usuarios, reportes, etc.).
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
            PermissionSeeder::class,
            // Agregar aquí otros seeders cuando sean necesarios
        ]);
    }
}
