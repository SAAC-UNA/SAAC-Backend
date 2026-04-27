<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Process;
use App\Models\ElementApproval;

/**
 * Seeder de prueba para Aprobación de Elements (HU-010 modelo flexible).
 * Ejecutar con: php artisan db:seed --class=ApprovalElementsTestSeeder
 */
class ApprovalElementsTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. VERIFICAR DATOS BASE
        $procesos = Process::count();
        $usuarios = User::count();

        if ($procesos === 0 || $usuarios === 0) {
            echo "❌ ERROR: Primero ejecuta los seeders principales (migrate:fresh --seed)\n";
            return;
        }

        echo "✓ Datos base: {$usuarios} usuarios, {$procesos} procesos\n\n";

        // 2. OBTENER USUARIOS DE PRUEBA
        $encargadoRole = Role::where('name', 'Encargado de Acreditación')->first();
        $profesorRole  = Role::where('name', 'Profesor')->first();

        if (!$encargadoRole || !$profesorRole) {
            echo "❌ ERROR: Faltan roles requeridos (Encargado de Acreditación y/o Profesor).\n";
            return;
        }

        // Encargado (aprueba/rechaza)
        $encargado = User::whereHas('roles', fn($q) => $q->where('roles.id', $encargadoRole->id))->first();
        if (!$encargado) {
            $encargado = User::create([
                'cedula'   => '222222222',
                'nombre'   => 'Encargado Acreditación Test',
                'email'    => 'encargado.Elements@saac.una.ac.cr',
                'password' => Hash::make('password123'),
                'status'   => 'active',
            ]);
            $encargado->assignRole($encargadoRole);
            echo "✓ Encargado creado\n";
        } else {
            echo "✓ Encargado: {$encargado->nombre}\n";
        }

        // Profesor (se le asignan Elements)
        $profesor = User::whereHas('roles', fn($q) => $q->where('roles.id', $profesorRole->id))->first();
        if (!$profesor) {
            echo "❌ ERROR: No hay usuario Profesor en el sistema\n";
            return;
        }
        echo "✓ Profesor: {$profesor->nombre}\n";

        $proceso = Process::first();
        echo "✓ Proceso: {$proceso->proceso_id}\n\n";

        // 3. OBTENER modelo_estructura_id flexible (ID=2)
        $modeloId = DB::table('MODELO_ESTRUCTURA')->where('nombre', 'like', '%flexible%')
            ->orWhere('nombre', 'like', '%SINAES 2026%')
            ->value('modelo_estructura_id');

        if (!$modeloId) {
            $modeloId = DB::table('MODELO_ESTRUCTURA')->value('modelo_estructura_id');
        }

        if (!$modeloId) {
            echo "❌ ERROR: No hay MODELO_ESTRUCTURA en la BD\n";
            return;
        }

        echo "✓ Modelo estructura ID: {$modeloId}\n\n";

        // 4. CREAR ÁRBOL DE ElementS (si no existen ya)
        echo "Creando árbol de Elements de prueba...\n";

        // Limpiar Elements de test previos (hijos antes que padres por FK RESTRICT)
        $testIds = DB::table('ELEMENTO')->where('nomenclatura', 'like', 'TEST-%')->pluck('elemento_id');
        if ($testIds->isNotEmpty()) {
            DB::table('ELEMENTO_ASIGNACION')->whereIn('elemento_id', $testIds)->delete();
            DB::table('APROBACION_ELEMENTO')->whereIn('elemento_id', $testIds)->delete();
            // Borrar hojas primero, luego intermedios, luego raíces
            foreach (['fuente', 'pauta', 'dimension'] as $tipo) {
                DB::table('ELEMENTO')->where('nomenclatura', 'like', 'TEST-%')->where('tipo', $tipo)->delete();
            }
            // Por si hay tipos distintos, borra lo que quede
            DB::table('ELEMENTO')->where('nomenclatura', 'like', 'TEST-%')->delete();
        }

        // Raíz: Dimensión (NO se puede aprobar — tiene hijos)
        $dimensionId = DB::table('ELEMENTO')->insertGetId([
            'modelo_estructura_id' => $modeloId,
            'padre_id'             => null,
            'tipo'                 => 'dimension',
            'nombre'               => 'Dimensión de prueba D1',
            'categoria'            => null,
            'nomenclatura'         => 'TEST-D1',
            'descripcion'          => '[TEST] Dimensión raíz — NO se puede aprobar directamente',
            'activo'               => true,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // Nivel intermedio: Pauta (NO se puede aprobar — tiene hijos)
        $pautaId = DB::table('ELEMENTO')->insertGetId([
            'modelo_estructura_id' => $modeloId,
            'padre_id'             => $dimensionId,
            'tipo'                 => 'pauta',
            'nombre'               => 'Pauta de prueba P1',
            'categoria'            => 'A',
            'nomenclatura'         => 'TEST-P1',
            'descripcion'          => '[TEST] Pauta con fuentes — NO se puede aprobar directamente',
            'activo'               => true,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // Hoja 1: Fuente aprobable
        $fuente1Id = DB::table('ELEMENTO')->insertGetId([
            'modelo_estructura_id' => $modeloId,
            'padre_id'             => $pautaId,
            'tipo'                 => 'fuente',
            'nombre'               => 'Fuente de prueba F1.1',
            'categoria'            => null,
            'nomenclatura'         => 'TEST-F1.1',
            'descripcion'          => '[TEST] Fuente 1 — SÍ se puede aprobar (es hoja)',
            'activo'               => true,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // Hoja 2: Fuente rechazable
        $fuente2Id = DB::table('ELEMENTO')->insertGetId([
            'modelo_estructura_id' => $modeloId,
            'padre_id'             => $pautaId,
            'tipo'                 => 'fuente',
            'nombre'               => 'Fuente de prueba F1.2',
            'categoria'            => null,
            'nomenclatura'         => 'TEST-F1.2',
            'descripcion'          => '[TEST] Fuente 2 — SÍ se puede rechazar (es hoja)',
            'activo'               => true,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        echo "✓ Árbol creado:\n";
        echo "    Dimensión TEST-D1 → ID: {$dimensionId} ✅ aprueba cascada total\n";
        echo "      Pauta TEST-P1  → ID: {$pautaId} ✅ aprueba cascada (F1.1 + F1.2)\n";
        echo "        Fuente TEST-F1.1 → ID: {$fuente1Id} ✅ aprueba solo esta fuente\n";
        echo "        Fuente TEST-F1.2 → ID: {$fuente2Id} ✅ aprueba solo esta fuente\n\n";

        // 5. CREAR ASIGNACIONES EN ELEMENTO_ASIGNACION
        echo "Creando asignaciones en ELEMENTO_ASIGNACION...\n";

        // Limpiar asignaciones previas de estos Elements
        DB::table('ELEMENTO_ASIGNACION')
            ->whereIn('elemento_id', [$fuente1Id, $fuente2Id])
            ->where('proceso_id', $proceso->proceso_id)
            ->delete();

        DB::table('ELEMENTO_ASIGNACION')->insert([
            [
                'elemento_id'  => $fuente1Id,
                'usuario_id'   => $profesor->usuario_id,
                'proceso_id'   => $proceso->proceso_id,
                'asignado_por' => $encargado->usuario_id,
                'estado'       => 'Completado',
                'fecha_limite' => now()->addDays(30),
                'comentario'   => 'Asignación completada — lista para aprobar',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'elemento_id'  => $fuente2Id,
                'usuario_id'   => $profesor->usuario_id,
                'proceso_id'   => $proceso->proceso_id,
                'asignado_por' => $encargado->usuario_id,
                'estado'       => 'En Progreso',
                'fecha_limite' => now()->addDays(15),
                'comentario'   => 'Asignación en progreso — documentación incompleta',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        echo "✓ 2 asignaciones creadas para el profesor\n\n";

        // 6. CREAR APROBACIONES DE EJEMPLO
        echo "Creando aprobaciones de ejemplo en APROBACION_ELEMENTO...\n";

        // Limpiar aprobaciones previas
        ElementApproval::whereIn('elemento_id', [$fuente1Id, $fuente2Id])
            ->where('proceso_id', $proceso->proceso_id)
            ->delete();

        $aprobada = ElementApproval::create([
            'elemento_id' => $fuente1Id,
            'proceso_id'  => $proceso->proceso_id,
            'usuario_id'  => $encargado->usuario_id,
            'estado'      => 'aprobado',
            'comentario'  => 'Fuente aprobada — documentación completa y válida',
        ]);

        $rechazada = ElementApproval::create([
            'elemento_id' => $fuente2Id,
            'proceso_id'  => $proceso->proceso_id,
            'usuario_id'  => $encargado->usuario_id,
            'estado'      => 'rechazado',
            'comentario'  => 'Fuente rechazada — falta firma del responsable',
        ]);

        echo "✓ Aprobación de ejemplo creada (ID: {$aprobada->aprobacion_elemento_id})\n";
        echo "✓ Rechazo de ejemplo creado (ID: {$rechazada->aprobacion_elemento_id})\n\n";

        // RESUMEN
        $sep = str_repeat('═', 57);
        echo "{$sep}\n";
        echo "  DATOS DE PRUEBA LISTOS — APROBACIÓN ElementS (HU-010)\n";
        echo "{$sep}\n\n";

        echo "USUARIOS:\n";
        foreach (User::with('roles')->get() as $u) {
            $roles = $u->roles->pluck('name')->join(', ');
            echo "  • {$u->nombre}\n";
            echo "    cedula: {$u->cedula}  password: password123\n";
            echo "    roles: {$roles}\n\n";
        }

        echo "IDs IMPORTANTES:\n";
        echo "  proceso_id            : {$proceso->proceso_id}\n";
        echo "  encargado usuario_id  : {$encargado->usuario_id}\n";
        echo "  profesor  usuario_id  : {$profesor->usuario_id}\n";
        echo "  dimension_id (raíz)   : {$dimensionId}  ← cascada: aprueba TODO el árbol\n";
        echo "  pauta_id (intermedio) : {$pautaId}       ← cascada: aprueba F1.1 + F1.2\n";
        echo "  fuente1_id (hoja)     : {$fuente1Id}     ← YA aprobada (aprueba solo esta)\n";
        echo "  fuente2_id (hoja)     : {$fuente2Id}     ← YA rechazada (aprueba solo esta)\n";
        echo "  aprobacion_id ejemplo : {$aprobada->aprobacion_elemento_id}\n\n";

        echo "ENDPOINTS:\n";
        echo "  GET  /api/aprobaciones-elementos\n";
        echo "  GET  /api/aprobaciones-elementos/{$aprobada->aprobacion_elemento_id}\n";
        echo "  POST /api/elementos/{$fuente1Id}/aprobar   ← ya aprobada, dará 422\n";
        echo "  POST /api/elementos/{$fuente2Id}/rechazar  ← ya rechazada, dará 422\n";
        echo "  POST /api/elementos/{$pautaId}/aprobar     ← CASCADA: aprueba pauta + F1.1 + F1.2\n";
        echo "  POST /api/elementos/{$dimensionId}/aprobar ← CASCADA: aprueba dimensión + pauta + F1.1 + F1.2\n\n";
        echo "  (Limpia APROBACION_ELEMENTO y vuelve a intentar para aprobar/rechazar)\n\n";
        echo "{$sep}\n";
    }
}
