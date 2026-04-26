<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla para asignar compromisos de mejora a elementos específicos dentro de un proceso
        Schema::create('COMPROMISO_MEJORA_ELEMENTO_ASIGNACION', function (Blueprint $table) {
            // Claves foráneas hacia los compromisos de mejora y los elementos de asignación
            $table->unsignedBigInteger('compromiso_elemento_id');
            $table->unsignedBigInteger('elemento_asignacion_id');
            $table->foreign('compromiso_elemento_id', 'fk_cmea_comp')
                ->references('compromiso_elemento_id')
                ->on('COMPROMISO_MEJORA_ELEMENTO')
                ->onDelete('cascade');
            $table->foreign('elemento_asignacion_id', 'fk_cmea_elem_asig')
                ->references('elemento_asignacion_id')
                ->on('ELEMENTO_ASIGNACION')
                ->onDelete('cascade');
            // Comentario adicional sobre la asignación
            $table->string('comentario', 500)->nullable();
            // Timestamps
            $table->timestamps();
            // Clave primaria compuesta
            $table->primary(['compromiso_elemento_id', 'elemento_asignacion_id'], 'pk_cmea');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('COMPROMISO_MEJORA_ELEMENTO_ASIGNACION');
    }
};
