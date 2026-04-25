<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de elementos genéricos para modelos de estructura
        Schema::create('ELEMENTO', function (Blueprint $table) {
            // Clave primaria
            $table->id('elemento_id');
            // Llave foránea hacia el elemento padre (auto-relación)
            $table->unsignedBigInteger('padre_id')->nullable();
            // Llave foránea hacia el modelo de estructura al que pertenece este elemento
            $table->unsignedBigInteger('modelo_estructura_id')->comment('FK al modelo de estructura al que pertenece este elemento');
            // Tipo de elemento (ej. 'facultad', 'carrera', 'sede', 'pauta', etc.)
            $table->string('tipo', 30);
            // Campos comunes para cualquier tipo de elemento
            $table->string('nombre', 100)->nullable();
            // Categoria de relevancia, solo aplicable para elementos de tipo 'pauta'
            $table->enum('categoria', ['A', 'B', 'C', 'D'])->nullable()->comment('Categoria de relevancia. Solo aplica para tipo=pauta');
            // Nomenclatura específica, puede ser código o sigla dependiendo del tipo de elemento
            $table->string('nomenclatura', 20)->nullable();
            // Descripción general del elemento, útil para cualquier tipo
            $table->text('descripcion')->nullable();
            // Estado de actividad del elemento
            $table->boolean('activo')->default(true);
            $table->enum('estado', ['Pendiente', 'En Progreso', 'Completado', 'Vencido'])->default('Pendiente');
            // Fecha límite para completar el elemento, relevante para pautas o tareas
            $table->date('fecha_limite')->nullable();
            // Timestamps para auditoría
            $table->timestamps();
            // Restricciones de integridad referencial
            $table->foreign('padre_id', 'elemento_padre_id_foreign')->references('elemento_id')->on('ELEMENTO')->onDelete('restrict');
            $table->foreign('modelo_estructura_id', 'elemento_modelo_estructura_id_foreign')->references('modelo_estructura_id')->on('MODELO_ESTRUCTURA')->onDelete('restrict');

            // Índices
            $table->index('padre_id', 'idx_elem_padre_id');
            $table->index('tipo', 'idx_elem_tipo');
            $table->index('activo', 'idx_elem_activo');
            $table->index('modelo_estructura_id', 'idx_elem_modelo');
            $table->index('categoria', 'idx_elem_categoria');
            $table->index(['padre_id', 'tipo'], 'idx_elem_padre_tipo');
            $table->index('estado', 'idx_elem_estado');
            $table->index('fecha_limite', 'idx_elem_fecha_limite');
            $table->fullText(['nomenclatura', 'descripcion'], 'ft_elemento_busqueda');
        });

        Schema::table('ARCHIVO', function (Blueprint $table) {
            $table->foreign('elemento_id', 'ar_elemento_id_foreign')
                ->references('elemento_id')
                ->on('ELEMENTO')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('ARCHIVO', function (Blueprint $table) {
            $table->dropForeign('ar_elemento_id_foreign');
        });

        Schema::dropIfExists('ELEMENTO');
    }
};
