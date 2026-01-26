<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Career;

class AssignCareersToUsersSeeder extends Seeder
{
    /**
     * Seeder simplificado que SOLO asigna carreras a usuarios existentes.
     * No crea usuarios ni roles, solo actualiza la tabla CARRERA_USUARIO.
     */
    public function run(): void
    {
        $this->command->info('🔗 Asignando carreras a usuarios existentes...');

        /**
         * Buscar usuarios por email
         */
        $adminInge = User::where('email', 'jose.jara.arias@est.una.ac.cr')->first();
        $adminQuimi = User::where('email', 'alejandro.ugalde.villalobos@est.una.ac.cr')->first();

        if (!$adminInge) {
            $this->command->warn('⚠️  Usuario jose.jara.arias@est.una.ac.cr no encontrado');
        }
        
        if (!$adminQuimi) {
            $this->command->warn('⚠️  Usuario alejandro.ugalde.villalobos@est.una.ac.cr no encontrado');
        }

        /**
         * Buscar carreras por nombre
         */
        $careerIng = Career::where('nombre', 'LIKE', '%Ingeniería en Sistemas%')->first();
        $careerQuimi = Career::where('nombre', 'LIKE', '%Química%')->first();

        if (!$careerIng) {
            $this->command->error('❌ Carrera "Ingeniería en Sistemas" no encontrada');
            return;
        }

        if (!$careerQuimi) {
            $this->command->error('❌ Carrera "Química" no encontrada');
            return;
        }

        $this->command->info("✅ Carrera Ingeniería encontrada: {$careerIng->nombre} (ID: {$careerIng->carrera_id})");
        $this->command->info("✅ Carrera Química encontrada: {$careerQuimi->nombre} (ID: {$careerQuimi->carrera_id})");

        /**
         * Limpiar asignaciones previas de estos usuarios (evitar duplicados)
         */
        $usuariosIds = array_filter([
            $adminInge?->usuario_id,
            $adminQuimi?->usuario_id
        ]);

        if (!empty($usuariosIds)) {
            $deletedCount = DB::table('CARRERA_USUARIO')
                ->whereIn('usuario_id', $usuariosIds)
                ->delete();
            
            if ($deletedCount > 0) {
                $this->command->info("🗑️  Eliminadas {$deletedCount} asignaciones previas");
            }
        }

        /**
         * Asignar carreras a usuarios
         */
        $insertions = [];

        if ($adminInge) {
            $insertions[] = [
                'usuario_id' => $adminInge->usuario_id,
                'carrera_id' => $careerIng->carrera_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($adminQuimi) {
            $insertions[] = [
                'usuario_id' => $adminQuimi->usuario_id,
                'carrera_id' => $careerQuimi->carrera_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($insertions)) {
            DB::table('CARRERA_USUARIO')->insert($insertions);
            $this->command->info("✅ {count($insertions)} carreras asignadas exitosamente");
        } else {
            $this->command->warn('⚠️  No se realizaron asignaciones (usuarios o carreras no encontrados)');
        }

        /**
         * Mostrar resumen
         */
        $this->command->newLine();
        $this->command->info('📊 Resumen de asignaciones:');
        
        if ($adminInge) {
            $careers = $adminInge->careers()->get();
            $this->command->info("   👤 {$adminInge->nombre}: {$careers->pluck('nombre')->join(', ')}");
        }
        
        if ($adminQuimi) {
            $careers = $adminQuimi->careers()->get();
            $this->command->info("   👤 {$adminQuimi->nombre}: {$careers->pluck('nombre')->join(', ')}");
        }

        $this->command->newLine();
        $this->command->info('🎉 Proceso completado');
    }
}
