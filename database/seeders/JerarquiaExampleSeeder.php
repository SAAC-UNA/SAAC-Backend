<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JerarquiaExampleSeeder extends Seeder
{
    /**
     * Seed para crear datos de ejemplo en JERARQUIA
     * Estructura basada en SINAES 2026 con pautas y fuentes
     * 
     * Ejecutar con: php artisan db:seed --class=JerarquiaExampleSeeder
     */
    public function run(): void
    {
        $this->command->info('🌱 Iniciando seed de JERARQUIA con datos de ejemplo...');

        // Limpiar tabla (opcional, comentar si no quieres limpiar)
        // DB::table('JERARQUIA')->truncate();

        // ============================================
        // Dimensión principal (nivel raíz)
        // ============================================
        $dimensionId = DB::table('JERARQUIA')->insertGetId([
            'modelo_estructura_id' => 2, // SINAES 2026 - Flexible
            'parent_id' => null,
            'nombre' => 'Formación Profesional',
            'tipo' => 'dimension',
            'categoria' => null, // Dimensiones no tienen categoría
            'nomenclatura' => 'D1',
            'descripcion' => 'Dimensión orientada a la formación integral del estudiante',
            'orden' => 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("✅ Dimensión creada: ID {$dimensionId}");

        // ============================================
        // Pauta 1 bajo la dimensión
        // ============================================
        $pauta1Id = DB::table('JERARQUIA')->insertGetId([
            'modelo_estructura_id' => 2,
            'parent_id' => $dimensionId,
            'nombre' => 'Pauta 1: Plan de Estudios',
            'tipo' => 'pauta',
            'categoria' => 'A', // Categoría A = Mayor importancia
            'nomenclatura' => 'P1',
            'descripcion' => 'Aspectos relacionados con el diseño y actualización del plan de estudios',
            'orden' => 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("✅ Pauta 1 creada: ID {$pauta1Id}");

        // Fuentes de información bajo Pauta 1
        $fuente1_1 = DB::table('JERARQUIA')->insertGetId([
            'modelo_estructura_id' => 2,
            'parent_id' => $pauta1Id,
            'nombre' => 'Plan de estudios vigente',
            'tipo' => 'fuente',
            'categoria' => null, // Fuentes no tienen categoría
            'nomenclatura' => 'F1.1',
            'descripcion' => 'Documento oficial del plan de estudios aprobado',
            'orden' => 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fuente1_2 = DB::table('JERARQUIA')->insertGetId([
            'modelo_estructura_id' => 2,
            'parent_id' => $pauta1Id,
            'nombre' => 'Mallas curriculares',
            'tipo' => 'fuente',
            'categoria' => null,
            'nomenclatura' => 'F1.2',
            'descripcion' => 'Estructuras de cursos y requisitos',
            'orden' => 2,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("✅ Fuentes 1.1 y 1.2 creadas");

        // ============================================
        // Pauta 2 bajo la dimensión
        // ============================================
        $pauta2Id = DB::table('JERARQUIA')->insertGetId([
            'modelo_estructura_id' => 2,
            'parent_id' => $dimensionId,
            'nombre' => 'Pauta 2: Perfil de Egreso',
            'tipo' => 'pauta',
            'categoria' => 'B', // Categoría B
            'nomenclatura' => 'P2',
            'descripcion' => 'Define las competencias y habilidades del egresado',
            'orden' => 2,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("✅ Pauta 2 creada: ID {$pauta2Id}");

        // Fuentes de información bajo Pauta 2
        $fuente2_1 = DB::table('JERARQUIA')->insertGetId([
            'modelo_estructura_id' => 2,
            'parent_id' => $pauta2Id,
            'nombre' => 'Documento de perfil de egreso',
            'tipo' => 'fuente',
            'categoria' => null,
            'nomenclatura' => 'F2.1',
            'descripcion' => 'Perfil académico y profesional del egresado',
            'orden' => 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fuente2_2 = DB::table('JERARQUIA')->insertGetId([
            'modelo_estructura_id' => 2,
            'parent_id' => $pauta2Id,
            'nombre' => 'Matriz de competencias',
            'tipo' => 'fuente',
            'categoria' => null,
            'nomenclatura' => 'F2.2',
            'descripcion' => 'Relación de competencias con cursos',
            'orden' => 2,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("✅ Fuentes 2.1 y 2.2 creadas");

        // ============================================
        // Otra dimensión para demostrar múltiples raíces
        // ============================================
        $dimension2Id = DB::table('JERARQUIA')->insertGetId([
            'modelo_estructura_id' => 2,
            'parent_id' => null,
            'nombre' => 'Gestión Académica y Administrativa',
            'tipo' => 'dimension',
            'categoria' => null,
            'nomenclatura' => 'D2',
            'descripcion' => 'Dimensión orientada a procesos administrativos',
            'orden' => 2,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("✅ Dimensión 2 creada: ID {$dimension2Id}");

        // Pauta bajo segunda dimensión
        $pauta3Id = DB::table('JERARQUIA')->insertGetId([
            'modelo_estructura_id' => 2,
            'parent_id' => $dimension2Id,
            'nombre' => 'Pauta 3: Gestión de Personal Académico',
            'tipo' => 'pauta',
            'categoria' => 'C', // Categoría C
            'nomenclatura' => 'P3',
            'descripcion' => 'Procesos de contratación y evaluación docente',
            'orden' => 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fuente3_1 = DB::table('JERARQUIA')->insertGetId([
            'modelo_estructura_id' => 2,
            'parent_id' => $pauta3Id,
            'nombre' => 'Currículos del personal académico',
            'tipo' => 'fuente',
            'categoria' => null, // Fuentes no tienen categoría
            'nomenclatura' => 'F3.1',
            'descripcion' => 'CVs actualizados de docentes',
            'orden' => 1,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("✅ Pauta 3 y Fuente 3.1 creadas");

        // ============================================
        // Resumen
        // ============================================
        $total = DB::table('JERARQUIA')->count();
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('✅ Seed completado exitosamente');
        $this->command->info('========================================');
        $this->command->info("📊 Total de elementos creados: {$total}");
        $this->command->info('');
        $this->command->info('Estructura creada:');
        $this->command->info('  📁 D1: Formación Profesional');
        $this->command->info('    📋 P1: Plan de Estudios');
        $this->command->info('      📄 F1.1: Plan de estudios vigente');
        $this->command->info('      📄 F1.2: Mallas curriculares');
        $this->command->info('    📋 P2: Perfil de Egreso');
        $this->command->info('      📄 F2.1: Documento de perfil de egreso');
        $this->command->info('      📄 F2.2: Matriz de competencias');
        $this->command->info('  📁 D2: Gestión Académica y Administrativa');
        $this->command->info('    📋 P3: Gestión de Personal Académico');
        $this->command->info('      📄 F3.1: Currículos del personal académico');
        $this->command->info('');
        $this->command->info('🔍 Puedes probar con:');
        $this->command->info('  GET /api/estructura/jerarquia');
        $this->command->info('  GET /api/estructura/jerarquia/arbol');
        $this->command->info('  GET /api/estructura/jerarquia?tipo=pauta');
    }
}
