<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Process;
use App\Models\ElementCommitment;
use App\Models\ElementAssignment;
use App\Models\StructureElement;

/**
 * Seeder de prueba para Compromisos de Mejora — modelo flexible (HU-010).
 *
 * PREREQUISITO: Ejecuta primero ApprovalElementsTestSeeder (crea árbol TEST-* y asignaciones).
 *
 * Ejecutar con:
 *   php artisan db:seed --class=CommitmentElementsTestSeeder
 *
 * Crea 3 compromisos para demostrar el comportamiento CASCADE:
 *   Compromiso 1 → TEST-F1.1 (fuente)  → 1 asignación (solo F1.1)
 *   Compromiso 2 → TEST-P1  (pauta)    → 2 asignaciones (F1.1 + F1.2 en cascada)
 *   Compromiso 3 → TEST-D1  (dimensión)→ 2 asignaciones (F1.1 + F1.2 en cascada, P1 no tiene asignación propia)
 */
class CommitmentElementsTestSeeder extends Seeder
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

        // ── 2. OBTENER ÁRBOL TEST (creado por ApprovalElementsTestSeeder) ─
        $dimension = StructureElement::where('nomenclatura', 'TEST-D1')->first();
        $pauta     = StructureElement::where('nomenclatura', 'TEST-P1')->first();
        $fuente1   = StructureElement::where('nomenclatura', 'TEST-F1.1')->first();
        $fuente2   = StructureElement::where('nomenclatura', 'TEST-F1.2')->first();

        if (!$dimension || !$pauta || !$fuente1 || !$fuente2) {
            echo "❌ ERROR: No se encontró el árbol TEST-*. Ejecuta primero:\n";
            echo "   php artisan db:seed --class=ApprovalElementsTestSeeder\n";
            return;
        }

        echo "✓ Árbol TEST encontrado:\n";
        echo "    TEST-D1 → ID: {$dimension->elemento_id}\n";
        echo "    TEST-P1 → ID: {$pauta->elemento_id}\n";
        echo "    TEST-F1.1 → ID: {$fuente1->elemento_id}\n";
        echo "    TEST-F1.2 → ID: {$fuente2->elemento_id}\n\n";

        // ── 3. OBTENER USUARIOS ──────────────────────────────────────────────
        $encargadoRole = Role::where('name', 'Encargado de Acreditación')->first();
        $profesorRole  = Role::where('name', 'Profesor')->first();

        $encargado = User::whereHas('roles', fn($q) => $q->where('roles.id', $encargadoRole->id))->first();
        if (!$encargado) {
            echo "❌ ERROR: No hay usuario Encargado de Acreditación.\n";
            return;
        }

        $profesor = User::whereHas('roles', fn($q) => $q->where('roles.id', $profesorRole->id))->first();
        if (!$profesor) {
            echo "❌ ERROR: No hay usuario Profesor.\n";
            return;
        }

        echo "✓ Encargado: {$encargado->nombre} (ID: {$encargado->usuario_id})\n";
        echo "✓ Profesor:  {$profesor->nombre} (ID: {$profesor->usuario_id})\n\n";

        // ── 4. OBTENER PROCESO (debe ser tipo 'Compromiso de mejora') ──────────
        $proceso = Process::where('tipo_proceso', 'Compromiso de mejora')->first();
        if (!$proceso) {
            echo "❌ ERROR: No hay procesos de tipo 'Compromiso de mejora'.\n";
            return;
        }
        echo "✓ Proceso ID: {$proceso->proceso_id} (tipo: {$proceso->tipo_proceso})\n\n";

        // ── 5. OBTENER ASIGNACIONES DEL ÁRBOL TEST ───────────────────────────
        $asignF1   = ElementAssignment::where('elemento_id', $fuente1->elemento_id)
            ->where('proceso_id', $proceso->proceso_id)
            ->first();

        $asignF2   = ElementAssignment::where('elemento_id', $fuente2->elemento_id)
            ->where('proceso_id', $proceso->proceso_id)
            ->first();

        // Si no existen, crearlas (por si el seeder de aprobaciones no se ejecutó)
        if (!$asignF1) {
            $asignF1 = ElementAssignment::create([
                'elemento_id'  => $fuente1->elemento_id,
                'usuario_id'   => $profesor->usuario_id,
                'proceso_id'   => $proceso->proceso_id,
                'asignado_por' => $encargado->usuario_id,
                'estado'       => 'Completado',
                'fecha_limite' => now()->addDays(30),
                'comentario'   => '[TEST] Asignación F1.1 creada por CommitmentElementsTestSeeder',
            ]);
            echo "  ✓ Asignación TEST-F1.1 creada (ID: {$asignF1->elemento_asignacion_id})\n";
        } else {
            echo "  ✓ Asignación TEST-F1.1 existente (ID: {$asignF1->elemento_asignacion_id})\n";
        }

        if (!$asignF2) {
            $asignF2 = ElementAssignment::create([
                'elemento_id'  => $fuente2->elemento_id,
                'usuario_id'   => $profesor->usuario_id,
                'proceso_id'   => $proceso->proceso_id,
                'asignado_por' => $encargado->usuario_id,
                'estado'       => 'En Progreso',
                'fecha_limite' => now()->addDays(15),
                'comentario'   => '[TEST] Asignación F1.2 creada por CommitmentElementsTestSeeder',
            ]);
            echo "  ✓ Asignación TEST-F1.2 creada (ID: {$asignF2->elemento_asignacion_id})\n";
        } else {
            echo "  ✓ Asignación TEST-F1.2 existente (ID: {$asignF2->elemento_asignacion_id})\n";
        }

        echo "\n";

        // ── 6. LIMPIAR COMPROMISOS TEST ANTERIORES ───────────────────────────
        echo "Limpiando compromisos TEST anteriores...\n";

        $testElementIds = [
            $dimension->elemento_id,
            $pauta->elemento_id,
            $fuente1->elemento_id,
            $fuente2->elemento_id,
        ];

        // Buscar compromisos via pivot que tengan asignaciones de los Elements TEST
        $compromisoIdsConAsignaciones = DB::table('COMPROMISO_MEJORA_ELEMENTO_ASIGNACION')
            ->join('ELEMENTO_ASIGNACION', 'COMPROMISO_MEJORA_ELEMENTO_ASIGNACION.elemento_asignacion_id',
                   '=', 'ELEMENTO_ASIGNACION.elemento_asignacion_id')
            ->whereIn('ELEMENTO_ASIGNACION.elemento_id', $testElementIds)
            ->pluck('COMPROMISO_MEJORA_ELEMENTO_ASIGNACION.compromiso_elemento_id')
            ->toArray();

        if (!empty($compromisoIdsConAsignaciones)) {
            // El CASCADE de la FK elimina automáticamente las filas del pivot
            ElementCommitment::whereIn('compromiso_elemento_id', $compromisoIdsConAsignaciones)->delete();
            echo "  ✓ " . count($compromisoIdsConAsignaciones) . " compromiso(s) TEST previo(s) eliminado(s)\n";
        } else {
            echo "  ✓ Sin compromisos TEST previos\n";
        }

        echo "\n";

        // ── 7. CREAR COMPROMISO 1: fuente individual (sin cascada) ───────────
        echo "Creando Compromiso 1 — TEST-F1.1 (fuente individual)...\n";

        $compromiso1 = ElementCommitment::create([
            'proceso_id'   => $proceso->proceso_id,
            'descripcion'  => '[TEST] Compromiso individual — solo fuente TEST-F1.1',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin'    => now()->addDays(30)->toDateString(),
            'estado'       => 'Pendiente',
            'activo'       => true,
        ]);

        // Vincular solo la asignación de F1.1 (sin cascada)
        $compromiso1->assignedElements()->attach($asignF1->elemento_asignacion_id, [
            'comentario' => 'Fuente con documentación completa, pendiente revisión',
        ]);

        echo "  ✓ Compromiso 1 creado (ID: {$compromiso1->compromiso_elemento_id})\n";
        echo "    → vinculada 1 asignación (ID: {$asignF1->elemento_asignacion_id} — TEST-F1.1)\n\n";

        // ── 8. CREAR COMPROMISO 2: pauta (cascada a F1.1 + F1.2) ────────────
        echo "Creando Compromiso 2 — TEST-P1 (pauta — cascada a F1.1 + F1.2)...\n";

        $compromiso2 = ElementCommitment::create([
            'proceso_id'   => $proceso->proceso_id,
            'descripcion'  => '[TEST] Compromiso pauta — vincula fuentes TEST-F1.1 y TEST-F1.2 en cascada',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin'    => now()->addDays(60)->toDateString(),
            'estado'       => 'En Progreso',
            'activo'       => true,
        ]);

        // Cascada manual: vincula asignaciones de F1.1 y F1.2 (hijas de P1)
        $compromiso2->assignedElements()->attach([
            $asignF1->elemento_asignacion_id => ['comentario' => 'Fuente 1 incluida por cascada desde pauta'],
            $asignF2->elemento_asignacion_id => ['comentario' => 'Fuente 2 incluida por cascada desde pauta'],
        ]);

        echo "  ✓ Compromiso 2 creado (ID: {$compromiso2->compromiso_elemento_id})\n";
        echo "    → vinculadas 2 asignaciones en cascada:\n";
        echo "       ID: {$asignF1->elemento_asignacion_id} — TEST-F1.1 (Completado)\n";
        echo "       ID: {$asignF2->elemento_asignacion_id} — TEST-F1.2 (En Progreso)\n\n";

        // ── 9. CREAR COMPROMISO 3: dimensión raíz (cascada total) ────────────
        echo "Creando Compromiso 3 — TEST-D1 (dimensión raíz — cascada total)...\n";

        $compromiso3 = ElementCommitment::create([
            'proceso_id'   => $proceso->proceso_id,
            'descripcion'  => '[TEST] Compromiso dimensión raíz — cascada total del árbol',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin'    => now()->addDays(90)->toDateString(),
            'estado'       => 'Pendiente',
            'activo'       => true,
        ]);

        // Cascada total: todas las asignaciones del árbol (P1 no tiene asignación propia)
        $compromiso3->assignedElements()->attach([
            $asignF1->elemento_asignacion_id => ['comentario' => 'Fuente 1 incluida por cascada desde dimensión'],
            $asignF2->elemento_asignacion_id => ['comentario' => 'Fuente 2 incluida por cascada desde dimensión'],
        ]);

        echo "  ✓ Compromiso 3 creado (ID: {$compromiso3->compromiso_elemento_id})\n";
        echo "    → vinculadas 2 asignaciones (P1 no tiene asignación propia, solo F1.1 y F1.2):\n";
        echo "       ID: {$asignF1->elemento_asignacion_id} — TEST-F1.1\n";
        echo "       ID: {$asignF2->elemento_asignacion_id} — TEST-F1.2\n\n";

        // ── RESUMEN ──────────────────────────────────────────────────────────
        $sep = str_repeat('═', 60);
        echo "{$sep}\n";
        echo "  DATOS DE PRUEBA — COMPROMISOS MEJORA ELEMENTOS (HU-010)\n";
        echo "{$sep}\n\n";

        echo "USUARIOS:\n";
        echo "  Encargado: {$encargado->nombre}\n";
        echo "    cedula: {$encargado->cedula}  password: password123\n";
        echo "    roles: Encargado de Acreditación\n\n";
        echo "  Profesor: {$profesor->nombre}\n";
        echo "    cedula: {$profesor->cedula}  password: password123\n";
        echo "    roles: Profesor\n\n";

        echo "IDs ÁRBOL ELEMENTO:\n";
        echo "  dimension_id  (TEST-D1)  : {$dimension->elemento_id}\n";
        echo "  pauta_id      (TEST-P1)  : {$pauta->elemento_id}\n";
        echo "  fuente1_id    (TEST-F1.1): {$fuente1->elemento_id}\n";
        echo "  fuente2_id    (TEST-F1.2): {$fuente2->elemento_id}\n\n";

        echo "IDs ASIGNACIONES:\n";
        echo "  asignacion_f1_id (F1.1): {$asignF1->elemento_asignacion_id}\n";
        echo "  asignacion_f2_id (F1.2): {$asignF2->elemento_asignacion_id}\n\n";

        echo "COMPROMISOS DE MEJORA CREADOS:\n";
        echo "  compromiso_1_id: {$compromiso1->compromiso_elemento_id}";
        echo " — fuente F1.1 (1 asignación)\n";
        echo "  compromiso_2_id: {$compromiso2->compromiso_elemento_id}";
        echo " — pauta P1 cascada (2 asignaciones)\n";
        echo "  compromiso_3_id: {$compromiso3->compromiso_elemento_id}";
        echo " — dimensión D1 cascada (2 asignaciones)\n\n";

        echo "proceso_id: {$proceso->proceso_id}\n\n";

        echo "ENDPOINTS:\n";
        echo "  GET  /api/compromisos-elementos\n";
        echo "  GET  /api/compromisos-elementos/{$compromiso1->compromiso_elemento_id}\n";
        echo "  GET  /api/compromisos-elementos/{$compromiso2->compromiso_elemento_id}\n";
        echo "  GET  /api/compromisos-elementos/{$compromiso3->compromiso_elemento_id}\n";
        echo "  GET  /api/compromisos-elementos/usuario/{$profesor->usuario_id}\n";
        echo "  GET  /api/compromisos-elementos/elemento/{$pauta->elemento_id}  ← cascada P1\n";
        echo "  GET  /api/compromisos-elementos/elemento/{$dimension->elemento_id}  ← cascada D1\n";
        echo "  POST /api/compromisos-elementos  ← crear nuevo\n";
        echo "  PUT  /api/compromisos-elementos/{$compromiso1->compromiso_elemento_id}  ← actualizar C1\n";
        echo "  PATCH /api/compromisos-elementos/{$compromiso1->compromiso_elemento_id}/active\n\n";

        echo "LIMPIAR DATOS TEST:\n";
        echo "  php artisan db:seed --class=CommitmentElementsTestSeeder\n";
        echo "  (el seeder limpia y recrea automáticamente)\n\n";

        echo "{$sep}\n";
    }
}
