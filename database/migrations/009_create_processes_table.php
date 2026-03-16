<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de procesos
        Schema::create('PROCESO', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('proceso_id');
            
            // Relación con ciclo de acreditación
            $table->foreignId('ciclo_acreditacion_id')
                  ->constrained('CICLO_ACREDITACION', 'ciclo_acreditacion_id')
                  ->onDelete('restrict');
            
            // Tipo de proceso (Autoevaluación, Compromiso de mejora, etc.)
            $table->string('tipo_proceso', 50);
            
            // Estado activo/inactivo (NO se eliminan físicamente, solo se desactivan)
            $table->boolean('activo')->default(true)
                  ->comment('true=activo, false=inactivo');
            
            // Timestamps de creación y actualización
            $table->timestamps();
            
            // Índices de performance
            $table->index('ciclo_acreditacion_id', 'idx_pr_ciclo_id');
            $table->index('tipo_proceso', 'idx_pr_tipo_proceso');
            $table->index('activo', 'idx_pr_activo');
        });
    }

    public function down(): void
    {
        // Elimina la tabla de procesos
        Schema::dropIfExists('PROCESO');
    }
};
