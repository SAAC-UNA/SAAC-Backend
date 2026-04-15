<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;
use App\Models\Process;

/**
 * FullSystemDemoSeeder — Datos de demostración para TODO el sistema SAAC.
 *
 * Pobla AMBOS modelos de acreditación con datos realistas de todos los RF:
 *   - MODELO TRADICIONAL (SINAES 2018): asignaciones, aprobaciones de criterio y evidencia,
 *     solicitudes de ampliación, compromisos de mejora.
 *   - MODELO FLEXIBLE (SINAES 2026): asignaciones de elementos, aprobaciones de elementos,
 *     solicitudes de ampliación de elementos, compromisos de mejora de elementos.
 *   - COMPARTIDO: notificaciones (todos los tipos), archivos adjuntos.
 *
 * IDEMPOTENTE: todas las filas se marcan con el prefijo [DEMO] en el campo de texto
 * libre (comentario / descripcion / motivo / titulo). Al inicio se eliminan las filas
 * previas con ese prefijo, por lo que se puede ejecutar múltiples veces.
 *
 * PREREQUISITOS (ejecutados automáticamente en DatabaseSeeder):
 *   - UserSeeder              → usuarios con roles
 *   - TraditionalStructureSeeder → DIMENSION / COMPONENTE / CRITERIO / ESTANDAR / EVIDENCIA
 *   - AccreditationCycleSeeder + ProcessSeeder → CICLO_ACREDITACION + PROCESO
 *   - FlexibleStructureSeeder → ELEMENTO (dimensión→pauta→fuente)
 */
class FullSystemDemoSeeder extends Seeder
{
    private const TAG = '[DEMO]';

    // ─────────────────────────────────────────────────────────────────────────
    // PUNTO DE ENTRADA
    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->command->info('🎬 FullSystemDemoSeeder — Iniciando carga de datos de demostración...');

        // 1. Verificar prerequisitos
        if (!$this->checkPrerequisites()) {
            return;
        }

        // 2. Obtener actores
        [$encargado, $profesor] = $this->getUsers();
        if (!$encargado || !$profesor) {
            return;
        }

        // 3. Obtener procesos activos
        [$procAutoeval, $procCompromiso] = $this->getActiveProcesses();
        if (!$procAutoeval || !$procCompromiso) {
            return;
        }

        // 4. Limpiar datos DEMO previos
        $this->command->info('🧹 Limpiando datos de demostración previos...');
        $this->cleanup();

        // 5. Crear ciclo flexible DEMO + sus procesos
        $this->command->info('');
        $this->command->info('🔧 Creando ciclo flexible DEMO...');
        [$procFlexAutoeval, $procFlexCompromiso] = $this->createFlexibleCiclo();
        if (!$procFlexAutoeval || !$procFlexCompromiso) {
            return;
        }

        // ─── MODELO TRADICIONAL ───────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('📚 TRADICIONAL (SINAES 2018)');

        $this->seedTradicional($encargado, $profesor, $procAutoeval, $procCompromiso);

        // ─── MODELO FLEXIBLE ─────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('🌿 FLEXIBLE (SINAES 2026)');

        $this->seedFlexible($encargado, $profesor, $procFlexAutoeval, $procFlexCompromiso);

        // ─── NOTIFICACIONES ──────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('🔔 Notificaciones');

        $this->seedNotificaciones($encargado, $profesor);

        // ─── ARCHIVOS ────────────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('📎 Archivos adjuntos');

        $this->seedArchivos($encargado, $profesor, $procAutoeval);

        $this->command->info('');
        $this->command->info('✅ FullSystemDemoSeeder finalizado correctamente.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PREREQUISITOS
    // ─────────────────────────────────────────────────────────────────────────

    private function checkPrerequisites(): bool
    {
        $checks = [
            'USUARIO'             => DB::table('USUARIO')->count(),
            'PROCESO'             => DB::table('PROCESO')->count(),
            'CRITERIO'            => DB::table('CRITERIO')->count(),
            'EVIDENCIA'           => DB::table('EVIDENCIA')->count(),
            'ELEMENTO (fuentes)'  => DB::table('ELEMENTO')
                ->where('tipo', 'fuente')
                ->where('nomenclatura', 'not like', 'TEST-%')
                ->count(),
        ];

        foreach ($checks as $label => $count) {
            if ($count === 0) {
                $this->command->error("❌ Prerequisito faltante: {$label} (0 registros). Ejecuta migrate:fresh --seed primero.");
                return false;
            }
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS DE LOOKUP
    // ─────────────────────────────────────────────────────────────────────────

    private function getUsers(): array
    {
        $encargadoRole = Role::where('name', 'Encargado de Acreditación')->first();
        $profesorRole  = Role::where('name', 'Profesor')->first();

        $encargado = $encargadoRole
            ? User::whereHas('roles', fn($q) => $q->where('roles.id', $encargadoRole->id))->first()
            : null;

        $profesor = $profesorRole
            ? User::whereHas('roles', fn($q) => $q->where('roles.id', $profesorRole->id))->first()
            : null;

        if (!$encargado) {
            $this->command->error('❌ No se encontró usuario con rol Encargado de Acreditación.');
            return [null, null];
        }

        if (!$profesor) {
            $this->command->error('❌ No se encontró usuario con rol Profesor.');
            return [null, null];
        }

        $this->command->info("✓ Encargado: {$encargado->nombre} (ID {$encargado->usuario_id})");
        $this->command->info("✓ Profesor:  {$profesor->nombre} (ID {$profesor->usuario_id})");

        return [$encargado, $profesor];
    }

    private function getActiveProcesses(): array
    {
        $procAutoeval = Process::where('tipo_proceso', 'Autoevaluación')
            ->where('activo', true)
            ->first();

        $procCompromiso = Process::where('tipo_proceso', 'Compromiso de mejora')
            ->where('activo', true)
            ->first();

        if (!$procAutoeval) {
            // Fall back to any Autoevaluación process
            $procAutoeval = Process::where('tipo_proceso', 'Autoevaluación')->first();
        }

        if (!$procCompromiso) {
            $procCompromiso = Process::where('tipo_proceso', 'Compromiso de mejora')->first();
        }

        if (!$procAutoeval || !$procCompromiso) {
            $this->command->error('❌ No se encontraron procesos de Autoevaluación y/o Compromiso de mejora.');
            return [null, null];
        }

        $this->command->info("✓ Proceso Autoevaluación:     ID {$procAutoeval->proceso_id}");
        $this->command->info("✓ Proceso Compromiso Mejora:  ID {$procCompromiso->proceso_id}");

        return [$procAutoeval, $procCompromiso];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LIMPIEZA DE DATOS DEMO PREVIOS (idempotencia)
    // ─────────────────────────────────────────────────────────────────────────

    private function cleanup(): void
    {
        // IDs de registros DEMO que tienen FKs RESTRICT apuntando a ellos
        $demoEvidAsignIds = DB::table('EVIDENCIA_ASIGNACION')
            ->where('comentario', 'like', self::TAG . '%')
            ->pluck('evidencia_asignacion_id');

        $demoEleAsignIds = DB::table('ELEMENTO_ASIGNACION')
            ->where('comentario', 'like', self::TAG . '%')
            ->pluck('elemento_asignacion_id');

        $demoAprobCritIds = DB::table('APROBACION_CRITERIO')
            ->where('comentario', 'like', self::TAG . '%')
            ->pluck('aprobacion_criterio_id');

        // Eliminar primero los hijos con FK RESTRICT
        if ($demoEvidAsignIds->isNotEmpty()) {
            DB::table('SOLICITUD_AMPLIACION')
                ->whereIn('evidencia_asignacion_id', $demoEvidAsignIds)
                ->delete();
        }

        if ($demoEleAsignIds->isNotEmpty()) {
            DB::table('SOLICITUD_AMPLIACION_ELEMENTO')
                ->whereIn('elemento_asignacion_id', $demoEleAsignIds)
                ->delete();
        }

        if ($demoAprobCritIds->isNotEmpty()) {
            DB::table('APROBACION_EVIDENCIA')
                ->whereIn('criterio_aprobacion_id', $demoAprobCritIds)
                ->delete();
        }

        // Compromisos (CASCADE a sus tablas pivot)
        DB::table('COMPROMISO_MEJORA')
            ->where('descripcion', 'like', self::TAG . '%')
            ->delete();

        DB::table('COMPROMISO_MEJORA_ELEMENTO')
            ->where('descripcion', 'like', self::TAG . '%')
            ->delete();

        // Aprobaciones padres
        DB::table('APROBACION_CRITERIO')
            ->where('comentario', 'like', self::TAG . '%')
            ->delete();

        DB::table('APROBACION_ELEMENTO')
            ->where('comentario', 'like', self::TAG . '%')
            ->delete();

        // Asignaciones (COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION hace CASCADE desde aquí)
        if ($demoEvidAsignIds->isNotEmpty()) {
            DB::table('EVIDENCIA_ASIGNACION')
                ->whereIn('evidencia_asignacion_id', $demoEvidAsignIds)
                ->delete();
        }

        if ($demoEleAsignIds->isNotEmpty()) {
            DB::table('ELEMENTO_ASIGNACION')
                ->whereIn('elemento_asignacion_id', $demoEleAsignIds)
                ->delete();
        }

        // Notificaciones y archivos
        DB::table('NOTIFICACION')
            ->where('titulo', 'like', self::TAG . '%')
            ->delete();

        DB::table('ARCHIVO')
            ->where('path', 'like', 'demo/%')
            ->delete();

        // Ciclo flexible DEMO + sus procesos (creados por este seeder)
        $demoCicloIds = DB::table('CICLO_ACREDITACION')
            ->where('nombre', 'like', self::TAG . '%')
            ->pluck('ciclo_acreditacion_id');

        if ($demoCicloIds->isNotEmpty()) {
            DB::table('PROCESO')
                ->whereIn('ciclo_acreditacion_id', $demoCicloIds)
                ->delete();
            DB::table('CICLO_ACREDITACION')
                ->whereIn('ciclo_acreditacion_id', $demoCicloIds)
                ->delete();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CICLO FLEXIBLE DEMO
    // ─────────────────────────────────────────────────────────────────────────

    private function createFlexibleCiclo(): array
    {
        $modeloFlexible = DB::table('MODELO_ESTRUCTURA')
            ->where('tipo', 'elemento_flexible')
            ->value('modelo_estructura_id');

        if (!$modeloFlexible) {
            $this->command->error('❌ No existe modelo elemento_flexible en MODELO_ESTRUCTURA.');
            return [null, null];
        }

        $carreraSede = DB::table('CARRERA_SEDE')->first();
        if (!$carreraSede) {
            $this->command->error('❌ No existe ninguna CARRERA_SEDE.');
            return [null, null];
        }

        $now = now();

        $cicloId = DB::table('CICLO_ACREDITACION')->insertGetId([
            'carrera_sede_id'      => $carreraSede->carrera_sede_id,
            'nombre'               => self::TAG . ' Ciclo Flexible 2026-2030',
            'modelo_estructura_id' => $modeloFlexible,
            'estado'               => 'activo',
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);

        $procAutoevalId = DB::table('PROCESO')->insertGetId([
            'ciclo_acreditacion_id' => $cicloId,
            'tipo_proceso'          => 'Autoevaluación',
            'fecha_inicio'          => $now->toDateString(),
            'fecha_finalizacion'    => $now->copy()->addMonths(8)->toDateString(),
            'activo'                => true,
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);

        $procCompromisoId = DB::table('PROCESO')->insertGetId([
            'ciclo_acreditacion_id' => $cicloId,
            'tipo_proceso'          => 'Compromiso de mejora',
            'fecha_inicio'          => $now->copy()->addMonth()->toDateString(),
            'fecha_finalizacion'    => $now->copy()->addYear()->toDateString(),
            'activo'                => true,
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);

        $this->command->info("  ✓ Ciclo Flexible DEMO creado (ID {$cicloId}, modelo_estructura_id {$modeloFlexible})");
        $this->command->info("  ✓ Proceso Autoevaluación flexible: ID {$procAutoevalId}");
        $this->command->info("  ✓ Proceso Compromiso Mejora flexible: ID {$procCompromisoId}");

        return [Process::find($procAutoevalId), Process::find($procCompromisoId)];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODELO TRADICIONAL (SINAES 2018)
    // ─────────────────────────────────────────────────────────────────────────

    private function seedTradicional(
        User    $encargado,
        User    $profesor,
        Process $procAutoeval,
        Process $procCompromiso
    ): void {
        $now = now();

        // ── 1. Obtener TODOS los criterios disponibles ──────────────────────
        $todosCriterios = DB::table('CRITERIO')
            ->orderBy('criterio_id')
            ->get();

        if ($todosCriterios->count() < 3) {
            $this->command->warn('⚠️  Tradicional: se necesitan al menos 3 criterios. Saltando sección.');
            return;
        }

        // Los primeros 6 cubren los flujos con estado específico
        $criterios        = $todosCriterios->take(6);
        $criterioAprobado  = $criterios->get(2); // 3er criterio → flujo completo aprobado
        $criterioRechazado = $criterios->get(3); // 4o criterio → flujo rechazado
        $criterioExtension = $criterios->get(4); // 5o criterio → evidencias con solicitud de ampliación

        // Criterios a reservar para flujos especiales (no duplicar en lote pendiente)
        $criteriosReservados = [
            $criterios->get(0)->criterio_id,
            $criterioAprobado->criterio_id,
            $criterioRechazado->criterio_id,
            $criterioExtension->criterio_id,
        ];

        // ── 2. Evidencias de cada criterio ───────────────────────────────────
        $evidsAprobado  = DB::table('EVIDENCIA')
            ->where('criterio_id', $criterioAprobado->criterio_id)
            ->where('activo', true)
            ->limit(3)
            ->pluck('evidencia_id');

        $evidsExtension = DB::table('EVIDENCIA')
            ->where('criterio_id', $criterioExtension->criterio_id)
            ->where('activo', true)
            ->limit(2)
            ->pluck('evidencia_id');

        $evidsCompromiso = DB::table('EVIDENCIA')
            ->where('criterio_id', $criterioRechazado->criterio_id)
            ->where('activo', true)
            ->limit(3)
            ->pluck('evidencia_id');

        if ($evidsAprobado->isEmpty() || $evidsExtension->isEmpty()) {
            $this->command->warn('⚠️  Tradicional: no hay evidencias suficientes. Saltando sección.');
            return;
        }

        // ── 3. EVIDENCIA_ASIGNACION ──────────────────────────────────────────
        // Criterio aprobado: todas sus evidencias completadas (base para aprobación de criterio)
        $evidAsignIds = [];
        foreach ($evidsAprobado as $evidId) {
            $evidAsignIds[] = DB::table('EVIDENCIA_ASIGNACION')->insertGetId([
                'proceso_id'      => $procAutoeval->proceso_id,
                'evidencia_id'    => $evidId,
                'usuario_id'      => $profesor->usuario_id,
                'estado'          => 'completado',
                'fecha_asignacion'=> $now->toDateTimeString(),
                'fecha_limite'    => $now->copy()->addDays(60)->toDateString(),
                'comentario'      => self::TAG . ' Evidencia completada — criterio aprobado (RF-02/RF-06)',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // Criterio extensión: en progreso (base para solicitud de ampliación tradicional)
        $evidExt1Id = DB::table('EVIDENCIA_ASIGNACION')->insertGetId([
            'proceso_id'      => $procAutoeval->proceso_id,
            'evidencia_id'    => $evidsExtension->first(),
            'usuario_id'      => $profesor->usuario_id,
            'estado'          => 'En Progreso',
            'fecha_asignacion'=> $now->toDateTimeString(),
            'fecha_limite'    => $now->copy()->addDays(15)->toDateString(),
            'comentario'      => self::TAG . ' Evidencia en progreso — para solicitud de ampliación (RF-15)',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // Criterio extensión: segunda evidencia pendiente
        $evidExt2Id = DB::table('EVIDENCIA_ASIGNACION')->insertGetId([
            'proceso_id'      => $procAutoeval->proceso_id,
            'evidencia_id'    => $evidsExtension->last(),
            'usuario_id'      => $profesor->usuario_id,
            'estado'          => 'Pendiente',
            'fecha_asignacion'=> $now->toDateTimeString(),
            'fecha_limite'    => $now->copy()->addDays(30)->toDateString(),
            'comentario'      => self::TAG . ' Evidencia pendiente — escenario base asignación (RF-06)',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // Historial: evidencia vencida
        $evidVencidaId = DB::table('EVIDENCIA_ASIGNACION')->insertGetId([
            'proceso_id'      => $procAutoeval->proceso_id,
            'evidencia_id'    => $evidsAprobado->first(),
            'usuario_id'      => $encargado->usuario_id,
            'estado'          => 'Vencido',
            'fecha_asignacion'=> $now->copy()->subDays(60)->toDateTimeString(),
            'fecha_limite'    => $now->copy()->subDays(5)->toDateString(),
            'comentario'      => self::TAG . ' Evidencia vencida — historial (RF-06)',
            'created_at'      => $now->copy()->subDays(60),
            'updated_at'      => $now,
        ]);

        // ── Lote PENDIENTE — evidencias de los criterios restantes ─────────
        //    Representa el trabajo pendiente normal del ciclo de acreditación
        $pendienteIds  = [];
        $enProgresoIds = [];

        foreach ($todosCriterios as $criterio) {
            // Saltar criterios ya usados en flujos especiales
            if (in_array($criterio->criterio_id, $criteriosReservados)) {
                continue;
            }

            $evids = DB::table('EVIDENCIA')
                ->where('criterio_id', $criterio->criterio_id)
                ->where('activo', true)
                ->orderBy('evidencia_id')
                ->get(['evidencia_id']);

            foreach ($evids as $idx => $evid) {
                // Primera evidencia de cada criterio → En Progreso (plazo ~20 días)
                if ($idx === 0) {
                    $enProgresoIds[] = DB::table('EVIDENCIA_ASIGNACION')->insertGetId([
                        'proceso_id'       => $procAutoeval->proceso_id,
                        'evidencia_id'     => $evid->evidencia_id,
                        'usuario_id'       => $profesor->usuario_id,
                        'estado'           => 'En Progreso',
                        'fecha_asignacion' => $now->toDateTimeString(),
                        'fecha_limite'     => $now->copy()->addDays(20)->toDateString(),
                        'comentario'       => self::TAG . " En progreso — criterio {$criterio->criterio_id} (RF-06)",
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                } else {
                    // Resto → Pendiente (plazo más holgado)
                    $pendienteIds[] = DB::table('EVIDENCIA_ASIGNACION')->insertGetId([
                        'proceso_id'       => $procAutoeval->proceso_id,
                        'evidencia_id'     => $evid->evidencia_id,
                        'usuario_id'       => $profesor->usuario_id,
                        'estado'           => 'Pendiente',
                        'fecha_asignacion' => $now->toDateTimeString(),
                        'fecha_limite'     => $now->copy()->addDays(45 + ($idx * 5))->toDateString(),
                        'comentario'       => self::TAG . " Pendiente — criterio {$criterio->criterio_id} evidencia #{$idx} (RF-06)",
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                }
            }
        }

        $totalEvidAsign = count($evidAsignIds) + 3 + count($pendienteIds) + count($enProgresoIds);
        $this->command->info("  ✓ {$totalEvidAsign} EVIDENCIA_ASIGNACION creadas");
        $this->command->info("      completado×" . count($evidAsignIds) . "  en_progreso×" . (1 + count($enProgresoIds)) . "  pendiente×" . (1 + count($pendienteIds)) . "  vencido×1");

        // ── 4. APROBACION_CRITERIO + APROBACION_EVIDENCIA ───────────────────
        // 4a. Criterio APROBADO (RF-06 / RF-12)
        $aprobCritId = DB::table('APROBACION_CRITERIO')->insertGetId([
            'criterio_id' => $criterioAprobado->criterio_id,
            'proceso_id'  => $procAutoeval->proceso_id,
            'usuario_id'  => $encargado->usuario_id,
            'estado'      => 'aprobado',
            'comentario'  => self::TAG . ' Criterio aprobado: cumple todos los estándares SINAES',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        // Aprobar cada evidencia asociada a este criterio (APROBACION_EVIDENCIA)
        foreach ($evidsAprobado as $evidId) {
            DB::table('APROBACION_EVIDENCIA')->insert([
                'evidencia_id'          => $evidId,
                'proceso_id'            => $procAutoeval->proceso_id,
                'criterio_aprobacion_id'=> $aprobCritId,
                'usuario_id'            => $encargado->usuario_id,
                'estado'                => 'aprobado',
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
        }

        // 4b. Criterio RECHAZADO (RF-06)
        DB::table('APROBACION_CRITERIO')->insertGetId([
            'criterio_id' => $criterioRechazado->criterio_id,
            'proceso_id'  => $procAutoeval->proceso_id,
            'usuario_id'  => $encargado->usuario_id,
            'estado'      => 'rechazado',
            'comentario'  => self::TAG . ' Criterio rechazado: falta documentación de soporte',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        // 4c. Criterio INCOMPLETO (RF-06 — estado intermedio)
        DB::table('APROBACION_CRITERIO')->insertGetId([
            'criterio_id' => $criterios->get(0)->criterio_id,
            'proceso_id'  => $procAutoeval->proceso_id,
            'usuario_id'  => $encargado->usuario_id,
            'estado'      => 'incompleto',
            'comentario'  => self::TAG . ' Criterio incompleto: evidencia E-01 pendiente de carga',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        $this->command->info("  ✓ APROBACION_CRITERIO (aprobado + rechazado + incompleto) y APROBACION_EVIDENCIA creadas");

        // ── 5. SOLICITUD_AMPLIACION (modelo tradicional — RF-15) ────────────
        // Solicitud PENDIENTE para evidencia en progreso
        DB::table('SOLICITUD_AMPLIACION')->insert([
            'evidencia_asignacion_id' => $evidExt1Id,
            'usuario_id'              => $profesor->usuario_id,
            'motivo'                  => 'El archivo de soporte requiere firma del coordinador y el proceso tomará más tiempo del previsto inicialmente.',
            'fecha_sugerida'          => $now->copy()->addDays(30)->toDateTimeString(),
            'estado'                  => 'pendiente',
            'created_at'              => $now,
            'updated_at'              => $now,
        ]);

        // Solicitud APROBADA (historial resuelto)
        DB::table('SOLICITUD_AMPLIACION')->insert([
            'evidencia_asignacion_id' => $evidExt2Id,
            'usuario_id'              => $profesor->usuario_id,
            'motivo'                  => 'El laboratorio de informática estuvo cerrado por mantenimiento durante la semana anterior. Se solicita 10 días adicionales.',
            'fecha_sugerida'          => $now->copy()->addDays(10)->toDateTimeString(),
            'estado'                  => 'aprobada',
            'fecha_resolucion'        => $now->copy()->subDays(2)->toDateTimeString(),
            'usuario_resolutor_id'    => $encargado->usuario_id,
            'justificacion'           => 'Se aprueba la extensión. El motivo es válido y el plazo solicitado es razonable.',
            'created_at'              => $now->copy()->subDays(5),
            'updated_at'              => $now,
        ]);

        // Solicitud RECHAZADA (historial resuelto)
        DB::table('SOLICITUD_AMPLIACION')->insert([
            'evidencia_asignacion_id' => $evidVencidaId,
            'usuario_id'              => $encargado->usuario_id,
            'motivo'                  => 'Solicitud enviada luego de que el plazo ya había vencido sin justificación previa.',
            'fecha_sugerida'          => $now->copy()->addDays(7)->toDateTimeString(),
            'estado'                  => 'rechazada',
            'fecha_resolucion'        => $now->copy()->subDays(4)->toDateTimeString(),
            'usuario_resolutor_id'    => $encargado->usuario_id,
            'justificacion'           => 'La solicitud fue presentada fuera del plazo reglamentario. No se admite extensión.',
            'created_at'              => $now->copy()->subDays(6),
            'updated_at'              => $now,
        ]);

        $this->command->info('  ✓ SOLICITUD_AMPLIACION (pendiente + aprobada + rechazada) creadas');

        // ── 6. COMPROMISO_MEJORA + pivots (RF-07 / RF-09) ──────────────────
        $compromisoId = DB::table('COMPROMISO_MEJORA')->insertGetId([
            'proceso_id'  => $procCompromiso->proceso_id,
            'descripcion' => self::TAG . ' Plan de mejora para la dimensión de Autoevaluación Institucional: fortalecer la evidencia documental de los criterios observados durante el ciclo 2026-2030.',
            'fecha_inicio'=> $now->copy()->addDays(5)->toDateString(),
            'fecha_fin'   => $now->copy()->addMonths(6)->toDateString(),
            'estado'      => 'Pendiente',
            'activo'      => true,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        // COMPROMISO_MEJORA_EVIDENCIA — evidencias del repositorio vinculadas al compromiso
        $evidCompromiso = DB::table('EVIDENCIA')
            ->whereIn('criterio_id', [$criterioRechazado->criterio_id])
            ->where('activo', true)
            ->limit(3)
            ->pluck('evidencia_id');

        foreach ($evidCompromiso as $eid) {
            DB::table('COMPROMISO_MEJORA_EVIDENCIA')->insert([
                'compromiso_mejora_id' => $compromisoId,
                'evidencia_id'         => $eid,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }

        // COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION — asignaciones vinculadas al compromiso
        foreach ($evidAsignIds as $eaId) {
            DB::table('COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION')->insert([
                'compromiso_mejora_id'   => $compromisoId,
                'evidencia_asignacion_id'=> $eaId,
                'comentario'             => self::TAG . ' Asignación priorizada en plan de mejora',
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);
        }

        $this->command->info('  ✓ COMPROMISO_MEJORA + EVIDENCIA + EVIDENCIA_ASIGNACION creados');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODELO FLEXIBLE (SINAES 2026)
    // ─────────────────────────────────────────────────────────────────────────

    private function seedFlexible(
        User    $encargado,
        User    $profesor,
        Process $procFlexAutoeval,
        Process $procFlexCompromiso
    ): void {
        $procAutoeval   = $procFlexAutoeval;
        $procCompromiso = $procFlexCompromiso;
        $now = now();

        // ── 1. Obtener fuentes reales del modelo flexible ────────────────────
        // Tomar hasta 20 fuentes reales para tener un lote amplio de asignaciones
        $todasFuentes = DB::table('ELEMENTO')
            ->where('tipo', 'fuente')
            ->where('nomenclatura', 'not like', 'TEST-%')
            ->orderBy('elemento_id')
            ->limit(20)
            ->get();

        if ($todasFuentes->count() < 3) {
            $this->command->warn('⚠️  Flexible: se necesitan al menos 3 fuentes reales. Saltando sección.');
            return;
        }

        // Las primeras 3 tienen flujos especiales (completado×2, en_progreso×1)
        $fuente1 = $todasFuentes->get(0);
        $fuente2 = $todasFuentes->get(1);
        $fuente3 = $todasFuentes->get(2);

        // ── 2. ELEMENTO_ASIGNACION ────────────────────────────────────────────
        // Fuente 1: Completada (para aprobar)
        $eleAsign1Id = DB::table('ELEMENTO_ASIGNACION')->insertGetId([
            'elemento_id'  => $fuente1->elemento_id,
            'usuario_id'   => $profesor->usuario_id,
            'proceso_id'   => $procAutoeval->proceso_id,
            'asignado_por' => $encargado->usuario_id,
            'estado'       => 'Completado',
            'fecha_limite' => $now->copy()->addDays(60)->toDateString(),
            'comentario'   => self::TAG . ' Fuente completada — flujo aprobación (RF-10)',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // Fuente 2: Completada (para aprobar)
        $eleAsign2Id = DB::table('ELEMENTO_ASIGNACION')->insertGetId([
            'elemento_id'  => $fuente2->elemento_id,
            'usuario_id'   => $profesor->usuario_id,
            'proceso_id'   => $procAutoeval->proceso_id,
            'asignado_por' => $encargado->usuario_id,
            'estado'       => 'Completado',
            'fecha_limite' => $now->copy()->addDays(45)->toDateString(),
            'comentario'   => self::TAG . ' Fuente completada — flujo aprobación (RF-10)',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // Fuente 3: En Progreso — próxima a vencer (para solicitud de ampliación)
        $eleAsign3Id = DB::table('ELEMENTO_ASIGNACION')->insertGetId([
            'elemento_id'  => $fuente3->elemento_id,
            'usuario_id'   => $profesor->usuario_id,
            'proceso_id'   => $procAutoeval->proceso_id,
            'asignado_por' => $encargado->usuario_id,
            'estado'       => 'En Progreso',
            'fecha_limite' => $now->copy()->addDays(8)->toDateString(),
            'comentario'   => self::TAG . ' Fuente en progreso — próxima a vencer, solicitud ampliación (RF-15)',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // ── Lote PENDIENTE / EN PROGRESO — fuentes 4 en adelante ─────────────
        //    Respetan UNIQUE(elemento_id, usuario_id, proceso_id)
        $eleAsignPendIds  = [];
        $eleAsignProgIds  = [];
        $fuentesUsadas    = [$fuente1->elemento_id, $fuente2->elemento_id, $fuente3->elemento_id];

        foreach ($todasFuentes->slice(3) as $idx => $fuente) {
            if (in_array($fuente->elemento_id, $fuentesUsadas)) {
                continue;
            }
            $fuentesUsadas[] = $fuente->elemento_id;

            // Alterar entre En Progreso (plazo corto) y Pendiente (plazo largo)
            if ($idx % 3 === 0) {
                // En Progreso con plazo moderado
                $eleAsignProgIds[] = DB::table('ELEMENTO_ASIGNACION')->insertGetId([
                    'elemento_id'  => $fuente->elemento_id,
                    'usuario_id'   => $profesor->usuario_id,
                    'proceso_id'   => $procAutoeval->proceso_id,
                    'asignado_por' => $encargado->usuario_id,
                    'estado'       => 'En Progreso',
                    'fecha_limite' => $now->copy()->addDays(20 + ($idx * 2))->toDateString(),
                    'comentario'   => self::TAG . " En progreso — {$fuente->nomenclatura} (RF-10)",
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            } else {
                // Pendiente con plazo escalonado
                $eleAsignPendIds[] = DB::table('ELEMENTO_ASIGNACION')->insertGetId([
                    'elemento_id'  => $fuente->elemento_id,
                    'usuario_id'   => $profesor->usuario_id,
                    'proceso_id'   => $procAutoeval->proceso_id,
                    'asignado_por' => $encargado->usuario_id,
                    'estado'       => 'Pendiente',
                    'fecha_limite' => $now->copy()->addDays(30 + ($idx * 3))->toDateString(),
                    'comentario'   => self::TAG . " Pendiente — {$fuente->nomenclatura} (RF-10)",
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        }

        $totalEle = 3 + count($eleAsignPendIds) + count($eleAsignProgIds);
        $this->command->info("  ✓ {$totalEle} ELEMENTO_ASIGNACION creadas");
        $this->command->info("      completado×2  en_progreso×" . (1+count($eleAsignProgIds)) . "  pendiente×" . count($eleAsignPendIds));

        // ── 3. APROBACION_ELEMENTO (RF-10) ────────────────────────────────────
        // Fuente 1: aprobada
        DB::table('APROBACION_ELEMENTO')->insert([
            'elemento_id'      => $fuente1->elemento_id,
            'proceso_id'       => $procAutoeval->proceso_id,
            'usuario_id'       => $encargado->usuario_id,
            'estado'           => 'aprobado',
            'comentario'       => self::TAG . ' Fuente aprobada: documentación completa y en regla',
            'nueva_fecha_limite'=> null,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        // Fuente 2: aprobada
        DB::table('APROBACION_ELEMENTO')->insert([
            'elemento_id'      => $fuente2->elemento_id,
            'proceso_id'       => $procAutoeval->proceso_id,
            'usuario_id'       => $encargado->usuario_id,
            'estado'           => 'aprobado',
            'comentario'       => self::TAG . ' Fuente aprobada: evidencia verificada y vigente',
            'nueva_fecha_limite'=> null,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        // Fuente 3: rechazada con nueva fecha límite sugerida
        DB::table('APROBACION_ELEMENTO')->insert([
            'elemento_id'       => $fuente3->elemento_id,
            'proceso_id'        => $procAutoeval->proceso_id,
            'usuario_id'        => $encargado->usuario_id,
            'estado'            => 'rechazado',
            'comentario'        => self::TAG . ' Fuente rechazada: el archivo no cumple con el formato oficial requerido por SINAES',
            'nueva_fecha_limite'=> $now->copy()->addDays(21)->toDateString(),
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        $this->command->info('  ✓ APROBACION_ELEMENTO (2 aprobadas + 1 rechazada) creadas');

        // ── 4. SOLICITUD_AMPLIACION_ELEMENTO (RF-15 flexible) ─────────────────
        // Solicitud PENDIENTE para fuente 3 (plazo urgente, 8 días)
        DB::table('SOLICITUD_AMPLIACION_ELEMENTO')->insert([
            'elemento_asignacion_id' => $eleAsign3Id,
            'usuario_id'             => $profesor->usuario_id,
            'motivo'                 => 'Pendiente la firma de validación del coordinador de área sobre la fuente de evidencia F2.1. El proceso administrativo está en trámite.',
            'fecha_sugerida'         => $now->copy()->addDays(21)->toDateTimeString(),
            'estado'                 => 'pendiente',
            'created_at'             => $now,
            'updated_at'             => $now,
        ]);

        // Solicitud APROBADA (historial)
        DB::table('SOLICITUD_AMPLIACION_ELEMENTO')->insert([
            'elemento_asignacion_id' => $eleAsign1Id,
            'usuario_id'             => $profesor->usuario_id,
            'motivo'                 => 'El repositorio central estuvo fuera de servicio durante 3 días hábiles impidiendo la carga del documento.',
            'fecha_sugerida'         => $now->copy()->addDays(7)->toDateTimeString(),
            'estado'                 => 'aprobada',
            'fecha_resolucion'       => $now->copy()->subDays(1)->toDateTimeString(),
            'usuario_resolutor_id'   => $encargado->usuario_id,
            'justificacion'          => 'Incidencia técnica confirmada. Se concede la extensión de 7 días.',
            'created_at'             => $now->copy()->subDays(4),
            'updated_at'             => $now,
        ]);

        $this->command->info('  ✓ SOLICITUD_AMPLIACION_ELEMENTO (pendiente + aprobada) creadas');

        // ── 5. COMPROMISO_MEJORA_ELEMENTO + pivots (RF-09 flexible) ──────────
        $compromisoElemId = DB::table('COMPROMISO_MEJORA_ELEMENTO')->insertGetId([
            'proceso_id'  => $procCompromiso->proceso_id,
            'descripcion' => self::TAG . ' Plan de mejora continua para el modelo SINAES 2026: reforzar la documentación de fuentes de evidencia en la dimensión de Gestión Académica.',
            'fecha_inicio'=> $now->copy()->addDays(7)->toDateString(),
            'fecha_fin'   => $now->copy()->addMonths(5)->toDateString(),
            'estado'      => 'Pendiente',
            'activo'      => true,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        // COMPROMISO_MEJORA_ELEMENTO_ASIGNACION — vincular asignaciones al compromiso
        $asignCompromisoElem = array_filter([$eleAsign1Id, $eleAsign2Id]);
        foreach ($asignCompromisoElem as $eaId) {
            DB::table('COMPROMISO_MEJORA_ELEMENTO_ASIGNACION')->insert([
                'compromiso_elemento_id' => $compromisoElemId,
                'elemento_asignacion_id' => $eaId,
                'comentario'             => self::TAG . ' Asignación prioritaria en plan de mejora continua SINAES 2026',
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);
        }

        $this->command->info('  ✓ COMPROMISO_MEJORA_ELEMENTO + ELEMENTO_ASIGNACION creados');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NOTIFICACIONES (RF-18)
    // ─────────────────────────────────────────────────────────────────────────

    private function seedNotificaciones(User $encargado, User $profesor): void
    {
        $now = now();

        // Obtener referencia a evidencia y elemento para los morfos
        $evidencia = DB::table('EVIDENCIA')->first();
        $elemento  = DB::table('ELEMENTO')
            ->where('tipo', 'fuente')
            ->where('nomenclatura', 'not like', 'TEST-%')
            ->first();

        $notificaciones = [
            // ─── Para el PROFESOR ─────────────────────────────────────────────
            [
                'usuario_id'     => $profesor->usuario_id,
                'tipo_evento'    => 'asignacion_evidencia',
                'canal'          => 'ambos',
                'titulo'         => self::TAG . ' Nueva evidencia asignada',
                'mensaje'        => 'Se te ha asignado la evidencia "Documentación del Plan de Estudios". Fecha límite: ' . now()->addDays(30)->format('d/m/Y'),
                'leida'          => false,
                'relacionado_type'=> $evidencia ? 'App\\Models\\Evidence' : null,
                'relacionado_id' => $evidencia?->evidencia_id,
                'enlace'         => '/evidencias/' . ($evidencia?->evidencia_id ?? 1),
                'estado_email'   => 'enviado',
                'metadatos'      => json_encode(['evidencia_id' => $evidencia?->evidencia_id, 'fecha_limite' => now()->addDays(30)->toDateString()]),
            ],
            [
                'usuario_id'     => $profesor->usuario_id,
                'tipo_evento'    => 'asignacion_elemento',
                'canal'          => 'ambos',
                'titulo'         => self::TAG . ' Nueva fuente de evidencia asignada (Modelo Flexible)',
                'mensaje'        => 'Se te ha asignado una fuente de evidencia del modelo SINAES 2026. Fecha límite: ' . now()->addDays(45)->format('d/m/Y'),
                'leida'          => false,
                'relacionado_type'=> $elemento ? 'App\\Models\\StructureElement' : null,
                'relacionado_id' => $elemento?->elemento_id,
                'enlace'         => '/elementos/' . ($elemento?->elemento_id ?? 1),
                'estado_email'   => 'enviado',
                'metadatos'      => json_encode(['elemento_id' => $elemento?->elemento_id, 'fecha_limite' => now()->addDays(45)->toDateString()]),
            ],
            [
                'usuario_id'     => $profesor->usuario_id,
                'tipo_evento'    => 'vencimiento_plazo',
                'canal'          => 'ambos',
                'titulo'         => self::TAG . ' ⚠️ Plazo vence en 3 días',
                'mensaje'        => 'La evidencia asignada vence el ' . now()->addDays(3)->format('d/m/Y') . '. Acción urgente requerida.',
                'leida'          => false,
                'relacionado_type'=> $evidencia ? 'App\\Models\\Evidence' : null,
                'relacionado_id' => $evidencia?->evidencia_id,
                'enlace'         => '/evidencias/' . ($evidencia?->evidencia_id ?? 1),
                'estado_email'   => 'enviado',
                'metadatos'      => json_encode(['dias_restantes' => 3, 'urgente' => true]),
            ],
            [
                'usuario_id'     => $profesor->usuario_id,
                'tipo_evento'    => 'respuesta_ampliacion',
                'canal'          => 'ambos',
                'titulo'         => self::TAG . ' Solicitud de ampliación aprobada',
                'mensaje'        => 'Tu solicitud de ampliación de plazo ha sido aprobada. Nueva fecha límite: ' . now()->addDays(10)->format('d/m/Y'),
                'leida'          => true,
                'fecha_lectura'  => $now->copy()->subHours(2)->toDateTimeString(),
                'enlace'         => '/solicitudes-ampliacion/1',
                'estado_email'   => 'enviado',
                'metadatos'      => json_encode(['aprobada' => true, 'nueva_fecha' => now()->addDays(10)->toDateString()]),
            ],
            [
                'usuario_id'     => $profesor->usuario_id,
                'tipo_evento'    => 'carga_archivo',
                'canal'          => 'interno',
                'titulo'         => self::TAG . ' Archivo subido correctamente',
                'mensaje'        => 'Tu archivo "plan_estudios_2026.pdf" se ha subido exitosamente y está en revisión.',
                'leida'          => true,
                'fecha_lectura'  => $now->copy()->subDays(1)->toDateTimeString(),
                'enlace'         => '/archivos/1',
                'estado_email'   => 'no_aplica',
                'metadatos'      => json_encode(['nombre_archivo' => 'plan_estudios_2026.pdf', 'tamanio' => '3.2 MB']),
            ],
            [
                'usuario_id'     => $profesor->usuario_id,
                'tipo_evento'    => 'devolucion_observacion',
                'canal'          => 'ambos',
                'titulo'         => self::TAG . ' Evidencia devuelta con observaciones',
                'mensaje'        => 'El Encargado de Acreditación devolvió la evidencia con observaciones. Por favor revisa los comentarios y realiza las correcciones.',
                'leida'          => false,
                'relacionado_type'=> $evidencia ? 'App\\Models\\Evidence' : null,
                'relacionado_id' => $evidencia?->evidencia_id,
                'enlace'         => '/evidencias/' . ($evidencia?->evidencia_id ?? 1),
                'estado_email'   => 'enviado',
                'metadatos'      => json_encode(['requiere_correccion' => true]),
            ],

            // ─── Para el ENCARGADO ────────────────────────────────────────────
            [
                'usuario_id'     => $encargado->usuario_id,
                'tipo_evento'    => 'solicitud_ampliacion',
                'canal'          => 'ambos',
                'titulo'         => self::TAG . ' Nueva solicitud de ampliación de plazo',
                'mensaje'        => "El profesor {$profesor->nombre} ha solicitado ampliar el plazo de una evidencia hasta " . now()->addDays(15)->format('d/m/Y') . '.',
                'leida'          => false,
                'enlace'         => '/solicitudes-ampliacion',
                'estado_email'   => 'enviado',
                'metadatos'      => json_encode(['solicitante' => $profesor->nombre, 'nueva_fecha' => now()->addDays(15)->toDateString()]),
            ],
            [
                'usuario_id'     => $encargado->usuario_id,
                'tipo_evento'    => 'aprobacion_criterio',
                'canal'          => 'interno',
                'titulo'         => self::TAG . ' Criterio aprobado exitosamente',
                'mensaje'        => 'El criterio de "Información y Promoción" ha sido aprobado. Todas las evidencias cumplen con los requisitos SINAES.',
                'leida'          => true,
                'fecha_lectura'  => $now->copy()->subDays(2)->toDateTimeString(),
                'enlace'         => '/criterios',
                'estado_email'   => 'no_aplica',
                'metadatos'      => json_encode(['estado' => 'aprobado']),
            ],
            [
                'usuario_id'     => $encargado->usuario_id,
                'tipo_evento'    => 'aprobacion_elemento',
                'canal'          => 'interno',
                'titulo'         => self::TAG . ' Fuente de evidencia aprobada (SINAES 2026)',
                'mensaje'        => 'La fuente de evidencia del modelo flexible ha sido revisada y aprobada.',
                'leida'          => true,
                'fecha_lectura'  => $now->copy()->subHours(5)->toDateTimeString(),
                'relacionado_type'=> $elemento ? 'App\\Models\\StructureElement' : null,
                'relacionado_id' => $elemento?->elemento_id,
                'enlace'         => '/elementos/' . ($elemento?->elemento_id ?? 1),
                'estado_email'   => 'no_aplica',
                'metadatos'      => json_encode(['estado' => 'aprobado', 'modelo' => 'flexible']),
            ],
            [
                'usuario_id'     => $encargado->usuario_id,
                'tipo_evento'    => 'comentario_nuevo',
                'canal'          => 'interno',
                'titulo'         => self::TAG . ' Nuevo comentario en proceso de acreditación',
                'mensaje'        => "El profesor {$profesor->nombre} dejó un comentario en el proceso de autoevaluación activo.",
                'leida'          => false,
                'enlace'         => '/procesos',
                'estado_email'   => 'no_aplica',
                'metadatos'      => json_encode(['autor' => $profesor->nombre]),
            ],
            [
                'usuario_id'     => $encargado->usuario_id,
                'tipo_evento'    => 'actualizacion_sistema',
                'canal'          => 'interno',
                'titulo'         => self::TAG . ' Sistema actualizado a versión 2.1.0',
                'mensaje'        => 'Se han incorporado mejoras en el módulo de gestión de elementos del modelo SINAES 2026. Consulta las notas de la versión para más detalles.',
                'leida'          => false,
                'enlace'         => '/notas-version',
                'estado_email'   => 'no_aplica',
                'metadatos'      => json_encode(['version' => '2.1.0', 'modulos' => ['elementos', 'aprobaciones']]),
            ],
        ];

        foreach ($notificaciones as $n) {
            DB::table('NOTIFICACION')->insert(array_merge($n, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $this->command->info('  ✓ ' . count($notificaciones) . ' notificaciones creadas (todos los tipos de eventos)');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ARCHIVOS ADJUNTOS (RF-08)
    // ─────────────────────────────────────────────────────────────────────────

    private function seedArchivos(User $encargado, User $profesor, Process $procAutoeval): void
    {
        $now = now();

        $evidencia = DB::table('EVIDENCIA')->first();
        $elemento  = DB::table('ELEMENTO')
            ->where('tipo', 'fuente')
            ->where('nomenclatura', 'not like', 'TEST-%')
            ->first();

        $archivos = [
            // Archivo vinculado a evidencia (modelo tradicional)
            [
                'evidencia_id'  => $evidencia?->evidencia_id,
                'elemento_id'   => null,
                'usuario_id'    => $profesor->usuario_id,
                'proceso_id'    => $procAutoeval->proceso_id,
                'fecha_subida'  => $now->toDateTimeString(),
                'tipo'          => 'archivo',
                'path'          => 'demo/evidencias/plan_estudios_ingenieria_sistemas_2026.pdf',
                'url'           => null,
                'nombre_original'=> 'Plan de Estudios ISI 2026.pdf',
                'tamanio'       => 2457600,   // ~2.4 MB
                'tipo_mime'     => 'application/pdf',
                'is_publico'    => false,
            ],
            [
                'evidencia_id'  => $evidencia?->evidencia_id,
                'elemento_id'   => null,
                'usuario_id'    => $profesor->usuario_id,
                'proceso_id'    => $procAutoeval->proceso_id,
                'fecha_subida'  => $now->copy()->subDays(3)->toDateTimeString(),
                'tipo'          => 'archivo',
                'path'          => 'demo/evidencias/acta_reunion_comite_acreditacion_2025.pdf',
                'url'           => null,
                'nombre_original'=> 'Acta Reunión Comité Acreditación 2025.pdf',
                'tamanio'       => 1024000,   // ~1 MB
                'tipo_mime'     => 'application/pdf',
                'is_publico'    => false,
            ],
            // Archivo vinculado a elemento (modelo flexible)
            [
                'evidencia_id'  => null,
                'elemento_id'   => $elemento?->elemento_id,
                'usuario_id'    => $profesor->usuario_id,
                'proceso_id'    => $procAutoeval->proceso_id,
                'fecha_subida'  => $now->copy()->subDays(1)->toDateTimeString(),
                'tipo'          => 'archivo',
                'path'          => 'demo/elementos/reglamento_evaluacion_sinaes2026.docx',
                'url'           => null,
                'nombre_original'=> 'Reglamento de Evaluacion SINAES 2026.docx',
                'tamanio'       => 512000,    // ~0.5 MB
                'tipo_mime'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'is_publico'    => false,
            ],
            // Archivo público con enlace compartido
            [
                'evidencia_id'  => $evidencia?->evidencia_id,
                'elemento_id'   => null,
                'usuario_id'    => $encargado->usuario_id,
                'proceso_id'    => $procAutoeval->proceso_id,
                'fecha_subida'  => $now->copy()->subDays(7)->toDateTimeString(),
                'tipo'          => 'archivo',
                'path'          => 'demo/public/informe_autoevaluacion_2024_publico.pdf',
                'url'           => null,
                'nombre_original'=> 'Informe Autoevaluación 2024 (Público).pdf',
                'tamanio'       => 5242880,   // ~5 MB
                'tipo_mime'     => 'application/pdf',
                'is_publico'    => true,
                'token_publico' => (string) \Illuminate\Support\Str::uuid(),
                'link_expira_en'=> $now->copy()->addDays(30)->toDateTimeString(),
            ],
        ];

        foreach ($archivos as $archivo) {
            DB::table('ARCHIVO')->insert(array_merge($archivo, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $this->command->info('  ✓ ' . count($archivos) . ' archivos adjuntos creados (evidencia + elemento + público)');
    }
}
