<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Crea 5 asignaciones de elemento de prueba para RF-15.
 * Prerequisito: ApprovalElementsTestSeeder
 *
 * Escenarios:
 *   EA-PROX  - En Progreso, +5 dias   -> 201 (proxima a vencer, aparece en /proximas-vencer)
 *   EA-LEJOS - En Progreso, +60 dias  -> 201 (plazo amplio)
 *   EA-PEND  - Pendiente,   +30 dias  -> 201 (Pendiente no bloquea)
 *   EA-VALID - Validada,    +15 dias  -> 422 asignacion ya aprobada
 *   EA-VENC  - En Progreso, -10 dias  -> 422 plazo ya vencido
 *
 * Detecta slots libres respetando UNIQUE(elemento_id, usuario_id, proceso_id).
 */
class ElementAssignmentTestSeeder extends Seeder
{
    public function run(): void
    {
        $f11 = DB::table('ELEMENTO')->where('nomenclatura', 'TEST-F1.1')->first();
        $f12 = DB::table('ELEMENTO')->where('nomenclatura', 'TEST-F1.2')->first();

        if (!$f11 || !$f12) {
            $this->command->error('No se encontraron TEST-F1.1 / TEST-F1.2.');
            $this->command->error('Ejecuta primero: php artisan db:seed --class=ApprovalElementsTestSeeder');
            return;
        }

        $base = DB::table('ELEMENTO_ASIGNACION')
            ->whereIn('elemento_id', [$f11->elemento_id, $f12->elemento_id])
            ->first();

        if (!$base) {
            $this->command->error('No hay asignaciones base. Ejecuta ApprovalElementsTestSeeder primero.');
            return;
        }

        $usuarioId = $base->usuario_id;
        $ahora     = now();

        // Limpiar asignaciones anteriores de este seeder
        DB::table('ELEMENTO_ASIGNACION')
            ->where('comentario', 'like', '[EA-TEST-RF15]%')
            ->delete();

        // Combinaciones (elemento_id, proceso_id) ya usadas por este usuario
        $taken = DB::table('ELEMENTO_ASIGNACION')
            ->where('usuario_id', $usuarioId)
            ->whereIn('elemento_id', [$f11->elemento_id, $f12->elemento_id])
            ->get(['elemento_id', 'proceso_id'])
            ->map(fn($r) => $r->elemento_id . '-' . $r->proceso_id)
            ->toArray();

        // Todos los proceso IDs disponibles
        $allProcs = DB::table('PROCESO')->orderBy('proceso_id')->pluck('proceso_id')->all();

        // Patron de elementos para cada slot (mismo elemento puede usarse con proceso distinto)
        $pattern = [
            $f12->elemento_id,  // EA-PROX
            $f11->elemento_id,  // EA-LEJOS
            $f12->elemento_id,  // EA-PEND
            $f11->elemento_id,  // EA-VALID
            $f12->elemento_id,  // EA-VENC
        ];

        // Buscar proceso_id libre para cada slot
        $slots = [];
        foreach ($pattern as $eleId) {
            $found = false;
            foreach ($allProcs as $procId) {
                $key = $eleId . '-' . $procId;
                if (!in_array($key, $taken)) {
                    $slots[] = ['elemento_id' => $eleId, 'proceso_id' => $procId];
                    $taken[] = $key;
                    $found   = true;
                    break;
                }
            }
            if (!$found) {
                $this->command->error("Sin slots disponibles para elemento_id={$eleId}.");
                return;
            }
        }

        [$s1, $s2, $s3, $s4, $s5] = $slots;

        // ── Insertar las 5 asignaciones ────────────────────────────────────────
        $idProx = DB::table('ELEMENTO_ASIGNACION')->insertGetId([
            'elemento_id'  => $s1['elemento_id'],
            'proceso_id'   => $s1['proceso_id'],
            'usuario_id'   => $usuarioId,
            'estado'       => 'En Progreso',
            'fecha_limite' => $ahora->copy()->addDays(5)->toDateString(),
            'comentario'   => '[EA-TEST-RF15] Proxima a vencer - aparece en /proximas-vencer',
            'created_at'   => $ahora,
            'updated_at'   => $ahora,
        ]);

        $idLejos = DB::table('ELEMENTO_ASIGNACION')->insertGetId([
            'elemento_id'  => $s2['elemento_id'],
            'proceso_id'   => $s2['proceso_id'],
            'usuario_id'   => $usuarioId,
            'estado'       => 'En Progreso',
            'fecha_limite' => $ahora->copy()->addDays(60)->toDateString(),
            'comentario'   => '[EA-TEST-RF15] Lejos de vencer - caso base crear solicitud',
            'created_at'   => $ahora,
            'updated_at'   => $ahora,
        ]);

        $idPend = DB::table('ELEMENTO_ASIGNACION')->insertGetId([
            'elemento_id'  => $s3['elemento_id'],
            'proceso_id'   => $s3['proceso_id'],
            'usuario_id'   => $usuarioId,
            'estado'       => 'Pendiente',
            'fecha_limite' => $ahora->copy()->addDays(30)->toDateString(),
            'comentario'   => '[EA-TEST-RF15] Pendiente - estado Pendiente NO bloquea solicitud',
            'created_at'   => $ahora,
            'updated_at'   => $ahora,
        ]);

        $idValid = DB::table('ELEMENTO_ASIGNACION')->insertGetId([
            'elemento_id'  => $s4['elemento_id'],
            'proceso_id'   => $s4['proceso_id'],
            'usuario_id'   => $usuarioId,
            'estado'       => 'Validada',
            'fecha_limite' => $ahora->copy()->addDays(15)->toDateString(),
            'comentario'   => '[EA-TEST-RF15] Validada - debe dar 422 asignacion ya aprobada',
            'created_at'   => $ahora,
            'updated_at'   => $ahora,
        ]);

        $idVenc = DB::table('ELEMENTO_ASIGNACION')->insertGetId([
            'elemento_id'  => $s5['elemento_id'],
            'proceso_id'   => $s5['proceso_id'],
            'usuario_id'   => $usuarioId,
            'estado'       => 'En Progreso',
            'fecha_limite' => $ahora->copy()->subDays(10)->toDateString(),
            'comentario'   => '[EA-TEST-RF15] Plazo vencido - debe dar 422 plazo ya vencido',
            'created_at'   => $ahora,
            'updated_at'   => $ahora,
        ]);

        // ── Output ─────────────────────────────────────────────────────────────
        $u = DB::table('USUARIO')->where('usuario_id', $usuarioId)->first();

        $this->command->info('');
        $this->command->info('=================================================================');
        $this->command->info('  ElementAssignmentTestSeeder — IDs creados');
        $this->command->info('=================================================================');
        $this->command->info("  EA-PROX  (En Progreso, +5 dias):   elemento_asignacion_id = {$idProx}");
        $this->command->info("  EA-LEJOS (En Progreso, +60 dias):  elemento_asignacion_id = {$idLejos}");
        $this->command->info("  EA-PEND  (Pendiente,   +30 dias):  elemento_asignacion_id = {$idPend}");
        $this->command->info("  EA-VALID (Validada,    +15 dias):  elemento_asignacion_id = {$idValid}");
        $this->command->info("  EA-VENC  (En Progreso, -10 dias):  elemento_asignacion_id = {$idVenc}");
        $this->command->info('');
        $this->command->info("  Login profesor:  cedula={$u->cedula}  |  {$u->nombre}");
        $this->command->info('');
        $this->command->info('  POST /api/solicitudes-ampliacion-tiempo — resultados esperados:');
        $this->command->info("    elemento_asignacion_id = {$idProx}   ->  201 OK (En Progreso, plazo valido)");
        $this->command->info("    elemento_asignacion_id = {$idLejos}  ->  201 OK (En Progreso, plazo valido)");
        $this->command->info("    elemento_asignacion_id = {$idPend}   ->  201 OK (Pendiente no bloquea)");
        $this->command->info("    elemento_asignacion_id = {$idValid}  ->  422   (asignacion Validada = ya aprobada)");
        $this->command->info("    elemento_asignacion_id = {$idVenc}   ->  422   (plazo ya vencido)");
        $this->command->info('=================================================================');
        $this->command->info('');
    }
}