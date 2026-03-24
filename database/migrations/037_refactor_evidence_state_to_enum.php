<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración HU-013: Elimina la tabla ESTADO_EVIDENCIA y la FK estado_evidencia_id
 * en EVIDENCIA, reemplazándolas por una columna enum directa en la misma tabla.
 *
 * Estados definidos por el equipo:
 *   Pendiente   – evidencia recién creada, sin revisión
 *   En Proceso  – en construcción / siendo trabajada
 *   Completado  – asignación completada por el responsable
 *   Vencido     – plazo expirado sin completarse
 *   Aprobado    – aprobada por evaluación interna
 *   Rechazado   – rechazada, requiere correcciones
 *   Observada   – HU-013: marcada con observaciones por encargado/vicerrectoría
 *   Validada    – HU-013: validada formalmente por encargado/vicerrectoría
 */
return new class extends Migration
{
    private const ENUM_VALUES = ['Pendiente', 'En Proceso', 'Completado', 'Vencido', 'Aprobado', 'Rechazado', 'Observada', 'Validada'];

    public function up(): void
    {
        // 1. Eliminar FK e índice de estado en EVIDENCIA
        Schema::table('EVIDENCIA', function (Blueprint $table) {
            $table->dropForeign(['estado_evidencia_id']);
            $table->dropIndex('idx_ev_estado_id');
            $table->dropIndex('idx_ev_estado_activo');
            $table->dropColumn('estado_evidencia_id');
        });

        // 2. Agregar columna enum directa
        Schema::table('EVIDENCIA', function (Blueprint $table) {
            $table->enum('estado', self::ENUM_VALUES)->default('Pendiente')->after('criterio_id');
            $table->index('estado', 'idx_ev_estado');
            $table->index(['estado', 'activo'], 'idx_ev_estado_activo');
        });

        // 3. Eliminar tabla ESTADO_EVIDENCIA (ya sin referencias)
        Schema::dropIfExists('ESTADO_EVIDENCIA');
    }

    public function down(): void
    {
        // Recrear ESTADO_EVIDENCIA
        Schema::create('ESTADO_EVIDENCIA', function (Blueprint $table) {
            $table->id()->name('estado_evidencia_id');
            $table->string('nombre', 30);
            $table->timestamps();
        });

        // Insertar los 4 estados originales
        \Illuminate\Support\Facades\DB::table('ESTADO_EVIDENCIA')->insert([
            ['estado_evidencia_id' => 1, 'nombre' => 'Pendiente',    'created_at' => now(), 'updated_at' => now()],
            ['estado_evidencia_id' => 2, 'nombre' => 'En revisión',  'created_at' => now(), 'updated_at' => now()],
            ['estado_evidencia_id' => 3, 'nombre' => 'Aprobada',     'created_at' => now(), 'updated_at' => now()],
            ['estado_evidencia_id' => 4, 'nombre' => 'Rechazada',    'created_at' => now(), 'updated_at' => now()],
        ]);

        // Revertir EVIDENCIA: eliminar enum y restaurar FK
        Schema::table('EVIDENCIA', function (Blueprint $table) {
            $table->dropIndex('idx_ev_estado');
            $table->dropIndex('idx_ev_estado_activo');
            $table->dropColumn('estado');
        });

        Schema::table('EVIDENCIA', function (Blueprint $table) {
            $table->foreignId('estado_evidencia_id')
                ->default(1)
                ->constrained('ESTADO_EVIDENCIA', 'estado_evidencia_id')
                ->onDelete('restrict');
            $table->index('estado_evidencia_id', 'idx_ev_estado_id');
            $table->index(['estado_evidencia_id', 'activo'], 'idx_ev_estado_activo');
        });
    }
};
