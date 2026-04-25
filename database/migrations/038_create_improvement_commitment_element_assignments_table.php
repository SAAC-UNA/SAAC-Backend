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
            $table->foreignId('compromiso_elemento_id')
                ->constrained('COMPROMISO_MEJORA_ELEMENTO', 'compromiso_elemento_id')
                ->onDelete('cascade');
            $table->foreignId('elemento_asignacion_id')
                ->constrained('ELEMENTO_ASIGNACION', 'elemento_asignacion_id')
                ->onDelete('cascade');
            // Comentario adicional sobre la asignación
            $table->string('comentario', 500)->nullable();
            // Timestamps
            $table->timestamps();
            // Clave primaria compuesta
            $table->primary(['compromiso_elemento_id', 'elemento_asignacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('COMPROMISO_MEJORA_ELEMENTO_ASIGNACION');
    }
};
