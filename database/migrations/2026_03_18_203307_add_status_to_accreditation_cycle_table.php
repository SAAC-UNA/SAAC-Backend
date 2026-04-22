<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        Schema::table('CICLO_ACREDITACION', function (Blueprint $table) {
            //Esatdo del ciclo de acreditación
            $table->enum('estado', ['activo','inactivo', 'completado'])
            ->default('activo')
            ->after("nombre");

            // Restrccion de unicidad: no puede existir dos ciclo 
            // de acreditación con el mismo nombre en la misma carrera sede
            $table->unique(['nombre', 'carrera_sede_id'], 'unique_ciclo_acreditacion');

            // Índice para mejorar la performance en consultas por estado
            $table->index('estado', 'index_ciclo_acreditacion_estado');
            $table->index('carrera_sede_id', 'index_ciclo_acreditacion_carrera_sede_id');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('CICLO_ACREDITACION', function (Blueprint $table) {
            //Elimina indices
            $table->dropIndex('index_ciclo_acreditacion_estado');
            $table->dropIndex('index_ciclo_acreditacion_carrera_sede_id');
            //Elimina restricción de unicidad
            $table->dropUnique('unique_ciclo_acreditacion');
            //Elimina la columna estado
            $table->dropColumn('estado');
        });
    }
};
