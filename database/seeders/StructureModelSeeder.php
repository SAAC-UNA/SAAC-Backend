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
        $exists = DB::table('MODELO_ESTRUCTURA')->where('tipo', 'tradicional')->exists();

        if (!$exists) {
            DB::table('MODELO_ESTRUCTURA')->insert([
                'nombre'      => 'SINAES 2018 - Estructura Tradicional',
                'tipo'        => 'tradicional',
                'descripcion' => 'Modelo clásico SINAES: Dimensión > Componente > Criterio > Evidencia.',
                'version'     => '2018',
                'activo'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $this->command->info('  ✔ Modelo tradicional SINAES 2018 creado.');
        } else {
            $this->command->info('  ℹ  Modelo tradicional ya existe — omitido.');
        }
    }
}
