<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('CICLO_ACREDITACION', function (Blueprint $table) {
            $table->id('ciclo_acreditacion_id');
            $table->foreignId('carrera_sede_id')->constrained('CARRERA_SEDE', 'carrera_sede_id')->onDelete('restrict');
            $table->string('nombre', 50);
            // Estado del ciclo de acreditación (activo/inactivo)
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->unsignedBigInteger('modelo_estructura_id')
                  ->comment('Modelo SINAES que usa este ciclo');
            $table->timestamps();

            $table->foreign('modelo_estructura_id', 'ciclo_modelo_estructura_id_foreign')
                  ->references('modelo_estructura_id')
                  ->on('MODELO_ESTRUCTURA')
                  ->onDelete('restrict');

            $table->index('carrera_sede_id', 'idx_ca_carrera_sede_id');
            $table->index('modelo_estructura_id', 'idx_ca_modelo_estructura');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CICLO_ACREDITACION');
    }
};
