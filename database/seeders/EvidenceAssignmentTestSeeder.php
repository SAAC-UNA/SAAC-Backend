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
        echo "📝 Insertando datos de prueba para aprobación de criterios...\n";

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

        // Insertar asignaciones COMPLETADAS para todas las evidencias del Criterio 1
        foreach ($evidenciasCriterio1 as $evidenciaId) {
            DB::table('EVIDENCIA_ASIGNACION')->insert([
                'proceso_id' => 1,
                'evidencia_id' => $evidenciaId,
                'usuario_id' => 1,
                'estado' => 'completado',
                'fecha_asignacion' => now(),
                'fecha_limite' => now()->addDays(30),
                'comentario' => 'Evidencia completada para pruebas',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        echo "✅ Asignaciones completadas insertadas para Criterio 1\n";

        // Obtener evidencias del Criterio 2
        $evidenciasCriterio2 = DB::table('EVIDENCIA')
            ->where('criterio_id', 2)
            ->where('activo', 1)
            ->limit(4)
            ->get();

        echo "✅ Encontradas {$evidenciasCriterio2->count()} evidencias para Criterio 2\n";

        // Insertar asignaciones MIXTAS para el Criterio 2 (algunas completadas, otras pendientes)
        foreach ($evidenciasCriterio2 as $index => $evidencia) {
            $estado = ($index % 2 == 0) ? 'completado' : 'pendiente';
            
            DB::table('EVIDENCIA_ASIGNACION')->insert([
                'proceso_id' => 2,
                'evidencia_id' => $evidencia->evidencia_id,
                'usuario_id' => 1,
                'estado' => $estado,
                'fecha_asignacion' => now(),
                'fecha_limite' => now()->addDays(30),
                'comentario' => 'Evidencia de prueba mixta',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        echo "✅ Asignaciones mixtas insertadas para Criterio 2\n";

        // Mostrar resumen
        $resumen = DB::select("
            SELECT 
                c.criterio_id,
                c.nomenclatura AS criterio,
                COUNT(DISTINCT e.evidencia_id) AS total_evidencias,
                COUNT(DISTINCT CASE WHEN ea.estado = 'completado' THEN ea.evidencia_id END) AS completadas,
                COUNT(DISTINCT CASE WHEN ea.estado = 'pendiente' THEN ea.evidencia_id END) AS pendientes
            FROM CRITERIO c
            JOIN EVIDENCIA e ON c.criterio_id = e.criterio_id
            LEFT JOIN EVIDENCIA_ASIGNACION ea ON e.evidencia_id = ea.evidencia_id
            WHERE c.criterio_id IN (1, 2)
              AND e.activo = 1
            GROUP BY c.criterio_id, c.nomenclatura
            ORDER BY c.criterio_id
        ");

        echo "\n📊 RESUMEN DE ASIGNACIONES:\n";
        foreach ($resumen as $row) {
            echo "  Criterio {$row->criterio_id} ({$row->criterio}): {$row->completadas}/{$row->total_evidencias} completadas\n";
        }

        echo "\n🎉 Datos de prueba listos!\n";
        echo "\n📝 ENDPOINTS PARA PROBAR:\n";
        echo "  ✅ POST /api/criterios/1/aprobar (proceso_id: 1) - Debe APROBAR\n";
        echo "  ❌ POST /api/criterios/2/aprobar (proceso_id: 2) - Debe FALLAR\n";
    }
}
