<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de ciclos de acreditación
        Schema::create('CICLO_ACREDITACION', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id('ciclo_acreditacion_id');
            // Relación con carrera_sede
            $table->foreignId('carrera_sede_id')->constrained('CARRERA_SEDE', 'carrera_sede_id')->onDelete('restrict');
            // Nombre del ciclo de acreditación
            $table->string('nombre', 50);
            // Estado del ciclo de acreditación (activo/inactivo)
            $table->enum('estado', ['activo', 'inactivo', 'completado'])->default('activo');
            // Relación con modelo_estructura
            $table->unsignedBigInteger('modelo_estructura_id')->comment('Modelo SINAES que usa este ciclo');
            // Timestamps de creación y actualización
            $table->timestamps();
            // Clave foránea con modelo_estructura
            $table->foreign('modelo_estructura_id', 'ciclo_modelo_estructura_id_foreign')->references('modelo_estructura_id')->on('MODELO_ESTRUCTURA')->onDelete('restrict');

            // Índices
            $table->index('carrera_sede_id', 'idx_ca_carrera_sede_id');
            $table->index('modelo_estructura_id', 'idx_ca_modelo_estructura');
            $table->index('estado', 'index_ciclo_acreditacion_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CICLO_ACREDITACION');
    }
};
