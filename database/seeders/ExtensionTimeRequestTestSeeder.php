<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Process;
use App\Models\ExtensionRequest;
use App\Models\ElementExtensionRequest;
use App\Models\ElementAssignment;
use App\Models\EvidenceAssignment;
use App\Models\StructureElement;

/**
 * Seeder de prueba para Solicitudes de Ampliación — RF-15 (HU-015).
 *
 * Crea solicitudes para AMBOS modelos:
 *   - Modelo tradicional: solicitud ligada a evidencia_asignacion_id
 *   - Modelo flexible:    solicitud ligada a elemento_asignacion_id
 *
 * PREREQUISITO: Ejecuta primero ApprovalElementsTestSeeder (crea árbol TEST-* y asignaciones).
 *
 * Ejecutar con:
 *   php artisan db:seed --class=ExtensionTimeRequestTestSeeder
 *
 * Crea 3 solicitudes:
 *   1. PENDIENTE — para elemento_asignacion_id (TEST-F1.2, estado En Progreso)
 *   2. PENDIENTE — para elemento_asignacion_id (TEST-F1.1, estado Pendiente) [si existe asignación con fecha futura]
 *   3. APROBADA  — para elemento_asignacion_id (historial, ya resuelta)
 */
class ExtensionTimeRequestTestSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. VERIFICAR PREREQUISITOS ──────────────────────────────────────
        $procesos = Process::count();
        $usuarios = User::count();

        if ($procesos === 0 || $usuarios === 0) {
            echo "❌ ERROR: Primero ejecuta los seeders principales (migrate:fresh --seed)\n";
            return;
        }

        echo "✓ Datos base: {$usuarios} usuarios, {$procesos} procesos\n\n";

        // ── 2. OBTENER ÁRBOL TEST ────────────────────────────────────────────
        $fuente1 = StructureElement::where('nomenclatura', 'TEST-F1.1')->first();
        $fuente2 = StructureElement::where('nomenclatura', 'TEST-F1.2')->first();

        if (!$fuente1 || !$fuente2) {
            echo "❌ ERROR: No se encontró el árbol TEST-*. Ejecuta primero:\n";
            echo "   php artisan db:seed --class=ApprovalElementsTestSeeder\n";
            return;
        }

        echo "✓ Elementos TEST encontrados:\n";
        echo "    TEST-F1.1 → ID: {$fuente1->elemento_id}\n";
        echo "    TEST-F1.2 → ID: {$fuente2->elemento_id}\n\n";

        // ── 3. OBTENER USUARIOS ──────────────────────────────────────────────
        $encargadoRole = Role::where('name', 'Encargado de Acreditación')->first();
        $profesorRole  = Role::where('name', 'Profesor')->first();

        if (!$encargadoRole || !$profesorRole) {
            echo "❌ ERROR: Faltan roles requeridos (Encargado de Acreditación y/o Profesor).\n";
            return;
        }

        $encargado = User::whereHas('roles', fn($q) => $q->where('roles.id', $encargadoRole->id))->first();
        $profesor  = User::whereHas('roles', fn($q) => $q->where('roles.id', $profesorRole->id))->first();

        if (!$encargado || !$profesor) {
            echo "❌ ERROR: Se requieren usuarios con roles Encargado y Profesor.\n";
            return;
        }

        echo "✓ Encargado: {$encargado->nombre} (ID: {$encargado->usuario_id})\n";
        echo "✓ Profesor:  {$profesor->nombre} (ID: {$profesor->usuario_id})\n\n";

        // ── 4. OBTENER PROCESO ───────────────────────────────────────────────
        $proceso = Process::first();
        echo "✓ Proceso ID: {$proceso->proceso_id}\n\n";

        // ── 5. OBTENER O CREAR ASIGNACIONES DE ELEMENTOS ─────────────────────
        // Necesitamos asignaciones con fecha_limite FUTURA (no vencidas) para poder crear solicitudes
        $asignF1 = ElementAssignment::where('elemento_id', $fuente1->elemento_id)
            ->where('proceso_id', $proceso->proceso_id)
            ->where('usuario_id', $profesor->usuario_id)
            ->first();

        $asignF2 = ElementAssignment::where('elemento_id', $fuente2->elemento_id)
            ->where('proceso_id', $proceso->proceso_id)
            ->where('usuario_id', $profesor->usuario_id)
            ->first();

        // Asegurar que las asignaciones existan y tengan fecha_limite futura
        if (!$asignF1) {
            $asignF1 = ElementAssignment::create([
                'elemento_id'  => $fuente1->elemento_id,
                'usuario_id'   => $profesor->usuario_id,
                'proceso_id'   => $proceso->proceso_id,
                'asignado_por' => $encargado->usuario_id,
                'estado'       => 'Pendiente',
                'fecha_limite' => now()->addDays(10)->toDateString(),
                'comentario'   => '[TEST-RF15] Asignación TEST-F1.1 para probar solicitudes',
            ]);
            echo "  ✓ Asignación TEST-F1.1 creada (ID: {$asignF1->elemento_asignacion_id})\n";
        } else {
            // Si existe pero la fecha ya venció, extenderla para que podamos crear solicitud
            if ($asignF1->fecha_limite && $asignF1->fecha_limite->lt(now())) {
                $asignF1->update(['fecha_limite' => now()->addDays(10)->toDateString()]);
                echo "  ✓ Asignación TEST-F1.1 extendida (ID: {$asignF1->elemento_asignacion_id}) — fecha_limite actualizada\n";
            } else {
                echo "  ✓ Asignación TEST-F1.1 existente (ID: {$asignF1->elemento_asignacion_id})\n";
            }
        }

        if (!$asignF2) {
            $asignF2 = ElementAssignment::create([
                'elemento_id'  => $fuente2->elemento_id,
                'usuario_id'   => $profesor->usuario_id,
                'proceso_id'   => $proceso->proceso_id,
                'asignado_por' => $encargado->usuario_id,
                'estado'       => 'En Progreso',
                'fecha_limite' => now()->addDays(5)->toDateString(),
                'comentario'   => '[TEST-RF15] Asignación TEST-F1.2 para probar solicitudes',
            ]);
            echo "  ✓ Asignación TEST-F1.2 creada (ID: {$asignF2->elemento_asignacion_id})\n\n";
        } else {
            if ($asignF2->fecha_limite && $asignF2->fecha_limite->lt(now())) {
                $asignF2->update(['fecha_limite' => now()->addDays(5)->toDateString()]);
                echo "  ✓ Asignación TEST-F1.2 extendida (ID: {$asignF2->elemento_asignacion_id}) — fecha_limite actualizada\n\n";
            } else {
                echo "  ✓ Asignación TEST-F1.2 existente (ID: {$asignF2->elemento_asignacion_id})\n\n";
            }
        }

        // ── 6. LIMPIAR SOLICITUDES TEST ANTERIORES ───────────────────────────
        echo "Limpiando solicitudes TEST anteriores...\n";

        $eliminadas = ElementExtensionRequest::whereIn('elemento_asignacion_id', [
            $asignF1->elemento_asignacion_id,
            $asignF2->elemento_asignacion_id,
        ])->delete();

        echo "  ✓ {$eliminadas} solicitud(es) TEST previa(s) eliminada(s)\n\n";

        // ── 7. SOLICITUD 1: PENDIENTE para TEST-F1.2 (urgente, vence en 5 días) ─
        echo "Creando Solicitud 1 — PENDIENTE para TEST-F1.2 (modelo flexible)...\n";

        $solicitud1 = ElementExtensionRequest::create([
            'elemento_asignacion_id' => $asignF2->elemento_asignacion_id,
            'usuario_id'             => $profesor->usuario_id,
            'motivo'                 => '[TEST] El archivo de evidencia requiere revisión adicional con el comité. El plazo original no es suficiente para completar la documentación requerida.',
            'fecha_sugerida'         => now()->addDays(20)->format('Y-m-d H:i:s'),
            'estado'                 => ElementExtensionRequest::ESTADO_PENDIENTE,
        ]);

        echo "  ✓ Solicitud 1 creada (ID: {$solicitud1->solicitud_ampliacion_elemento_id})\n";
        echo "    → elemento_asignacion_id: {$asignF2->elemento_asignacion_id} (TEST-F1.2)\n";
        echo "    → estado: pendiente\n";
        echo "    → fecha_sugerida: " . now()->addDays(20)->format('Y-m-d') . "\n\n";

        // ── 8. SOLICITUD 2: PENDIENTE para TEST-F1.1 ────────────────────────
        echo "Creando Solicitud 2 — PENDIENTE para TEST-F1.1 (modelo flexible)...\n";

        $solicitud2 = ElementExtensionRequest::create([
            'elemento_asignacion_id' => $asignF1->elemento_asignacion_id,
            'usuario_id'             => $profesor->usuario_id,
            'motivo'                 => '[TEST] Se requiere recopilar información adicional de fuentes externas. El plazo actual no permite completar el análisis requerido por el estándar.',
            'fecha_sugerida'         => now()->addDays(25)->format('Y-m-d H:i:s'),
            'estado'                 => ElementExtensionRequest::ESTADO_PENDIENTE,
        ]);

        echo "  ✓ Solicitud 2 creada (ID: {$solicitud2->solicitud_ampliacion_elemento_id})\n";
        echo "    → elemento_asignacion_id: {$asignF1->elemento_asignacion_id} (TEST-F1.1)\n";
        echo "    → estado: pendiente\n";
        echo "    → fecha_sugerida: " . now()->addDays(25)->format('Y-m-d') . "\n\n";

        // ── 9. SOLICITUD 3: APROBADA (historial) ────────────────────────────
        echo "Creando Solicitud 3 — APROBADA (historial de solicitud resuelta)...\n";

        // Necesitamos una tercera asignación o reutilizar F1.1 pero con estado 'aprobada'
        // Usamos insert directo para no pasar por las validaciones del servicio
        $solicitud3 = ElementExtensionRequest::create([
            'elemento_asignacion_id' => $asignF1->elemento_asignacion_id,
            'usuario_id'             => $profesor->usuario_id,
            'motivo'                 => '[TEST] Solicitud histórica — ya fue aprobada por el encargado. Documentación de referencia para pruebas.',
            'fecha_sugerida'         => now()->subDays(5)->format('Y-m-d H:i:s'),
            'estado'                 => 'aprobada',
            'fecha_resolucion'       => now()->subDays(10)->format('Y-m-d H:i:s'),
            'usuario_resolutor_id'   => $encargado->usuario_id,
            'justificacion'          => '[TEST] Aprobada — el profesor demostró necesidad válida de extensión.',
            'created_at'             => now()->subDays(15),
        ]);

        echo "  ✓ Solicitud 3 creada (ID: {$solicitud3->solicitud_ampliacion_elemento_id})\n";
        echo "    → elemento_asignacion_id: {$asignF1->elemento_asignacion_id} (TEST-F1.1)\n";
        echo "    → estado: aprobada (historial)\n";
        echo "    → resuelta por: {$encargado->nombre}\n\n";

        // ── RESUMEN ──────────────────────────────────────────────────────────
        $sep = str_repeat('═', 60);
        echo "{$sep}\n";
        echo "  DATOS DE PRUEBA — SOLICITUDES AMPLIACIÓN RF-15 (ELEMENTOS)\n";
        echo "{$sep}\n\n";

        echo "AUTENTICACIÓN:\n";
        echo "  Profesor (crea solicitudes):\n";
        echo "    cedula:   {$profesor->cedula}\n";
        echo "    password: password123\n\n";
        echo "  Encargado (aprueba/rechaza — RF-16):\n";
        echo "    cedula:   {$encargado->cedula}\n";
        echo "    password: password123\n\n";

        echo "IDs ELEMENTOS:\n";
        echo "  fuente1_elemento_id  (TEST-F1.1): {$fuente1->elemento_id}\n";
        echo "  fuente2_elemento_id  (TEST-F1.2): {$fuente2->elemento_id}\n\n";

        echo "IDs ASIGNACIONES (usar en POST /solicitudes-ampliacion-tiempo):\n";
        echo "  asignacion_f1_id (TEST-F1.1, Pendiente, +10 días):   {$asignF1->elemento_asignacion_id}\n";
        echo "  asignacion_f2_id (TEST-F1.2, En Progreso, +5 días):  {$asignF2->elemento_asignacion_id}\n\n";

        echo "IDs SOLICITUDES CREADAS:\n";
        echo "  solicitud_1_id: {$solicitud1->solicitud_ampliacion_elemento_id} — PENDIENTE (F1.2)\n";
        echo "  solicitud_2_id: {$solicitud2->solicitud_ampliacion_elemento_id} — PENDIENTE (F1.1)\n";
        echo "  solicitud_3_id: {$solicitud3->solicitud_ampliacion_elemento_id} — APROBADA  (historial F1.1)\n\n";

        echo "NOTA: solicitud_2 y solicitud_3 apuntan ambas a asignacion_f1_id.\n";
        echo "  → solicitud_3 tiene estado='aprobada', por lo tanto NO bloquea crear solicitud_2 pendiente.\n";
        echo "  → Si intentas crear otra solicitud pendiente para F1.1, el sistema la rechazará\n";
        echo "     (regla: solo 1 solicitud pendiente por asignación).\n\n";

        echo "{$sep}\n";
        echo "  ENDPOINTS A PROBAR (ver docs/POSTMAN_RF15_EXTENSION_ELEMENTO.md)\n";
        echo "{$sep}\n";
        echo "  POST   /api/solicitudes-ampliacion-elemento\n";
        echo "  GET    /api/solicitudes-ampliacion-elemento\n";
        echo "  GET    /api/solicitudes-ampliacion-elemento/{id}\n";
        echo "  PUT    /api/solicitudes-ampliacion-elemento/{id}\n";
        echo "  DELETE /api/solicitudes-ampliacion-elemento/{id}\n";
        echo "  GET    /api/solicitudes-ampliacion-elemento/proximas-vencer\n";
        echo "  PATCH  /api/solicitudes-ampliacion-elemento/{id}/cancelar\n";
        echo "{$sep}\n";
    }
}
