<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal que ejecuta los demás seeders del sistema.
 *
 * ORDEN DE EJECUCIÓN (importante para integridad referencial):
 * 1. Estructura organizacional (Universidad -> Campus -> Carrera)
 * 2. Permisos y Roles
 * 3. Usuarios
 * 4. Relaciones (Usuarios-Carreras, etc.)
 * 5. Datos del dominio (Componentes, Dimensiones, Criterios, Evidencias)
 * 6. Datos operacionales (Procesos, Asignaciones, etc.)
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
        $this->command->info('🚀 Iniciando carga de datos del sistema SAAC-UNA...');
        $this->command->info('');

        $this->call([
            // === ESTRUCTURA ORGANIZACIONAL ===
            UniversitySeeder::class,            // 1. Universidad
            CampusSeeder::class,                // 2. Campus
            CareerSeeder::class,                // 3. Carreras
            CareerCampusSeeder::class,          // 4. Relación Carrera-Campus

            // === SEGURIDAD Y AUTENTICACIÓN ===
            PermissionSeeder::class,            // 5. Permisos del sistema
            // RolesAndPermissionsSeeder::class, // Opcional: alternativa combinada

            // === USUARIOS Y RELACIONES ===
            UserSeeder::class,                  // 6. Usuarios con roles y carreras
            CommentSeeder::class,               // 7. Comentarios (temporal - relación será refactorizada)

            // === DATOS DEL DOMINIO DE ACREDITACIÓN ===
            // Nota: modelo tradicional SINAES 2018 se inserta en la migración 007a (no requiere seeder)
            TraditionalStructureSeeder::class,  // 8-12. Dimensiones → Componentes → Criterios → Estándares → Evidencias

            // === CICLOS Y PROCESOS ===
            AccreditationCycleSeeder::class,    // 14. Ciclos de acreditación (dependen de carrera_sede)
            ProcessSeeder::class,               // 15. Procesos (dependen de ciclos)
            AccreditationReportSeeder::class,   // 16. Informes de acreditación SINAES (HU-027)
            AutoevaluationSeeder::class,        // 17. Autoevaluaciones (dependen de procesos tipo "Autoevaluación")
            ImprovementCommitmentSeeder::class, // 17. Compromisos de mejora (dependen de procesos tipo "Compromiso de mejora")

            // === ASIGNACIONES DE EVIDENCIAS ===
            EvidenceAssignmentTestSeeder::class, // 18. Asignaciones de evidencias (para pruebas de aprobación)

            // === APROBACIONES DE CRITERIOS (DATOS DE PRUEBA) ===
            AprobacionCriteriosTestSeeder::class, // 19. Aprobaciones de criterios (para pruebas de endpoints)

            // === MODELO FLEXIBLE (SINAES 2026) ===
            FlexibleStructureSeeder::class,    // 20. Estructura flexible SINAES 2026 (Dimensión→Pauta→Fuente)

            // === APROBACIONES DE ELEMENTOS - MODELO FLEXIBLE (DATOS DE PRUEBA) ===
            ApprovalElementsTestSeeder::class, // 21. Aprobaciones de elementos (HU-010 modelo flexible)

            // === COMPROMISOS DE MEJORA - MODELO FLEXIBLE (DATOS DE PRUEBA) ===
            CommitmentElementsTestSeeder::class, // 21. Compromisos de mejora elementos (HU-010 modelo flexible)

            // === DEMOSTRACIÓN COMPLETA DEL SISTEMA ===
            FullSystemDemoSeeder::class,         // 22. Datos de demo para TODOS los RF (ambos modelos)

            // === AUDITORÍA Y LOGS ===
            ActionTypeSeeder::class,          // 23. Tipos de acción (catálogo de TIPO_ACCION)
            // AuditLogSeeder::class,            // 22. Logs de auditoría
        ]);

        $this->command->info('');
        $this->command->info('✅ Todos los seeders se ejecutaron correctamente');
        $this->command->info('🎉 Sistema SAAC-UNA listo para usar!');
    }
}
