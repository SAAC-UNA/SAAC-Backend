<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla INFORME_ACREDITACION.
     *
     * HU-027: Publicación de informe de acreditación aprobado.
     * Almacena la resolución oficial de SINAES que certifica
     * la acreditación de una carrera en un ciclo dado.
     */
    public function up(): void
    {
        Schema::create('INFORME_ACREDITACION', function (Blueprint $table) {
            // Clave primaria
            $table->id('informe_acreditacion_id');

            // Ciclo de acreditación al que pertenece este informe (uno por ciclo)
            $table->foreignId('ciclo_acreditacion_id')
                  ->constrained('CICLO_ACREDITACION', 'ciclo_acreditacion_id')
                  ->onDelete('restrict');

            // Archivo PDF de la resolución SINAES (tabla ARCHIVO)
            $table->foreignId('archivo_id')
                  ->constrained('ARCHIVO', 'archivo_id')
                  ->onDelete('restrict');

            // Usuario que realizó la publicación (FK con nombre no estándar)
            $table->unsignedBigInteger('usuario_publicacion_id')
                  ->comment('Usuario que publicó el informe');
            $table->foreign('usuario_publicacion_id', 'ia_usuario_publicacion_fk')
                  ->references('usuario_id')
                  ->on('USUARIO')
                  ->onDelete('restrict');

            // Estado del informe en el sistema
            $table->enum('estado', ['publicado', 'despublicado'])
                  ->default('publicado')
                  ->comment('publicado=visible publicamente, despublicado=retirado');

            // Número oficial de resolución emitida por SINAES (único por institución)
            $table->string('numero_resolucion', 100)
                  ->comment('Ej: RES-SINAES-2026-045');

            // Fecha en que SINAES emitió la resolución
            $table->date('fecha_resolucion')
                  ->comment('Fecha oficial de la resolución de SINAES');

            // Período de vigencia de la acreditación
            $table->date('vigencia_desde')
                  ->comment('Inicio de la vigencia de la acreditación');
            $table->date('vigencia_hasta')
                  ->comment('Fin de la vigencia de la acreditación');

            // Fecha y hora exacta en que se publicó en el sistema
            $table->timestamp('fecha_publicacion')
                  ->comment('Momento en que el usuario realizó la publicación');

            // Observaciones opcionales del publicador
            $table->text('observaciones')->nullable()
                  ->comment('Comentarios adicionales del publicador');

            $table->timestamps();

            // Restricción: solo un informe publicado por ciclo de acreditación
            $table->unique('ciclo_acreditacion_id', 'unique_informe_por_ciclo');

            // Restricción: número de resolución único en todo el sistema
            $table->unique('numero_resolucion', 'unique_numero_resolucion');

            // Índices de performance
            $table->index('ciclo_acreditacion_id', 'idx_ia_ciclo_id');
            $table->index('archivo_id', 'idx_ia_archivo_id');
            $table->index('usuario_publicacion_id', 'idx_ia_usuario_pub');
            $table->index('estado', 'idx_ia_estado');
            $table->index('vigencia_hasta', 'idx_ia_vigencia_hasta');
        });
    }

    /**
     * Revierte la creación de la tabla.
     */
    public function down(): void
    {
        Schema::dropIfExists('INFORME_ACREDITACION');
    }
};
