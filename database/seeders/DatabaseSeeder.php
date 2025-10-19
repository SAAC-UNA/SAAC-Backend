<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal que ejecuta los demás seeders del sistema.
 * 
 * ORDEN DE EJECUCIÓN (importante para integridad referencial):
 * 1. Estructura organizacional (Universidad -> Campus -> Facultad -> Carrera)
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
            FacultySeeder::class,               // 3. Facultades
            CareerSeeder::class,                // 4. Carreras
            CareerCampusSeeder::class,          // 5. Relación Carrera-Campus
            
            // === SEGURIDAD Y AUTENTICACIÓN ===
            PermissionSeeder::class,            // 6. Permisos del sistema
            // RolesAndPermissionsSeeder::class, // Opcional: alternativa combinada
            
            // === USUARIOS Y RELACIONES ===
            UserSeeder::class,                  // 7. Usuarios con roles y carreras
            CommentSeeder::class,               // 8. Comentarios (temporal - relación será refactorizada)
            
            // === DATOS DEL DOMINIO DE ACREDITACIÓN ===
            DimensionSeeder::class,             // 9. Dimensiones
            ComponentSeeder::class,             // 10. Componentes (dependen de dimensiones)
            CriterionSeeder::class,             // 11. Criterios (dependen de componentes)
            StandardSeeder::class,              // 12. Estándares (dependen de criterios)
            EvidenceStateSeeder::class,         // 13. Estados de evidencia
            EvidenceSeeder::class,              // 14. Evidencias (dependen de criterios y estados)
            
            // === CICLOS Y PROCESOS ===
            AccreditationCycleSeeder::class,    // 15. Ciclos de acreditación (dependen de carrera_sede)
            ProcessSeeder::class,               // 16. Procesos (dependen de ciclos)
            AutoevaluationSeeder::class,        // 17. Autoevaluaciones (dependen de procesos tipo "Autoevaluación")
            ImprovementCommitmentSeeder::class, // 18. Compromisos de mejora (dependen de procesos tipo "Compromiso de mejora")
            
            // === AUDITORÍA Y LOGS ===
            // ActionTypeSeeder::class,          // 21. Tipos de acción
            // AuditLogSeeder::class,            // 22. Logs de auditoría
        ]);

        $this->command->info('');
        $this->command->info('✅ Todos los seeders se ejecutaron correctamente');
        $this->command->info('🎉 Sistema SAAC-UNA listo para usar!');
    }
}
