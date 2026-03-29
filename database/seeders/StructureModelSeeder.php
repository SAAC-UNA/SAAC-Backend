<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StructureModelSeeder extends Seeder
{
    /**
     * Inserta el modelo de estructura tradicional (SINAES 2018).
     *
     * Solo se inserta si no existe ya — seguro para correr múltiples veces.
     * El modelo tradicional es un singleton del sistema: representa el estándar
     * SINAES 2018 fijo (Dimensión > Componente > Criterio > Evidencia).
     *
     * Los modelos tipo elemento_flexible los crea el usuario desde el CRUD,
     * ya que cada institución define su propia estructura flexible.
     */
    public function run(): void
    {
        // El modelo tradicional SINAES 2018 se inserta directamente en la migración
        // 007a_create_modelo_estructura_table.php con insertOrIgnore.
        // No es necesario hacerlo aquí — corre solo con `php artisan migrate`.
        $this->command->info('  ℹ  Modelo tradicional gestionado por la migración 007a — nada que hacer.');
    }
}
