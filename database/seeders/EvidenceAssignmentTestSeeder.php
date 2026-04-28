<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvidenceAssignmentTestSeeder extends Seeder
{
    /**
     * Insertar asignaciones de evidencias completadas para pruebas de aprobación de criterios
     */
    public function run(): void
    {
        // Si no hay procesos (porque no hay modelos creados aún), omitir silenciosamente
        if (!DB::table('PROCESO')->exists()) {
            $this->command->warn('⚠️  EvidenceAssignmentTestSeeder omitido: no hay procesos en BD.');
            return;
        }

        echo "📝 Insertando datos de prueba para asignaciones de evidencias...\n";

        // Obtener todos los usuarios dinámicamente
        $usuarios = DB::table('USUARIO')->pluck('usuario_id');
        if ($usuarios->isEmpty()) {
            $this->command->warn('⚠️  EvidenceAssignmentTestSeeder omitido: no hay usuarios en BD.');
            return;
        }

        // Eliminar asignaciones previas de prueba
        DB::table('EVIDENCIA_ASIGNACION')
            ->whereIn('proceso_id', [1, 2])
            ->where('comentario', 'LIKE', '%prueba%')
            ->delete();

        // Obtener evidencias del Criterio 1
        $evidenciasCriterio1 = DB::table('EVIDENCIA')
            ->where('criterio_id', 1)
            ->where('activo', 1)
            ->pluck('evidencia_id');

        echo "✅ Encontradas {$evidenciasCriterio1->count()} evidencias para Criterio 1\n";

        // Obtener evidencias del Criterio 2
        $evidenciasCriterio2 = DB::table('EVIDENCIA')
            ->where('criterio_id', 2)
            ->where('activo', 1)
            ->limit(4)
            ->pluck('evidencia_id');

        echo "✅ Encontradas {$evidenciasCriterio2->count()} evidencias para Criterio 2\n";

        // Insertar asignaciones para TODOS los usuarios
        foreach ($usuarios as $usuarioId) {
            // Proceso 1 – asignaciones Completadas (para pruebas de aprobación de criterios)
            foreach ($evidenciasCriterio1 as $evidenciaId) {
                DB::table('EVIDENCIA_ASIGNACION')->insert([
                    'proceso_id'      => 1,
                    'evidencia_id'    => $evidenciaId,
                    'usuario_id'      => $usuarioId,
                    'estado'          => 'Completado',
                    'fecha_asignacion' => now(),
                    'fecha_limite'    => now()->addDays(30),
                    'comentario'      => 'Evidencia completada para pruebas',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            // Proceso 2 – asignaciones mixtas (Pendiente / En Progreso para flujos de trabajo)
            foreach ($evidenciasCriterio2 as $index => $evidenciaId) {
                $estado = ($index % 2 === 0) ? 'Pendiente' : 'En Progreso';

                DB::table('EVIDENCIA_ASIGNACION')->insert([
                    'proceso_id'      => 2,
                    'evidencia_id'    => $evidenciaId,
                    'usuario_id'      => $usuarioId,
                    'estado'          => $estado,
                    'fecha_asignacion' => now(),
                    'fecha_limite'    => now()->addDays(30),
                    'comentario'      => 'Evidencia de prueba mixta',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        echo "✅ Asignaciones insertadas para {$usuarios->count()} usuario(s)\n";

        // Mostrar resumen
        $total = DB::table('EVIDENCIA_ASIGNACION')->count();
        echo "\n📊 Total EVIDENCIA_ASIGNACION: {$total}\n";

        echo "\n🎉 Datos de prueba listos!\n";
        echo "\n📝 ENDPOINTS PARA PROBAR:\n";
        echo "  ✅ POST /api/criterios/1/aprobar (proceso_id: 1) - Debe APROBAR\n";
        echo "  ❌ POST /api/criterios/2/aprobar (proceso_id: 2) - Debe FALLAR\n";
    }
}
