<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla pivot COMPROMISO_MEJORA_ELEMENTO_ASIGNACION.
 * Equivalente a COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION del modelo tradicional,
 * pero vinculado a ELEMENTO_ASIGNACION del modelo flexible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('COMPROMISO_MEJORA_ELEMENTO_ASIGNACION', function (Blueprint $table) {
            $table->unsignedBigInteger('compromiso_elemento_id');
            $table->unsignedBigInteger('elemento_asignacion_id');

            $table->foreign('compromiso_elemento_id', 'fk_cmea_compromiso')
                  ->references('compromiso_elemento_id')
                  ->on('COMPROMISO_MEJORA_ELEMENTO')
                  ->onDelete('cascade');

            $table->foreign('elemento_asignacion_id', 'fk_cmea_asignacion')
                  ->references('elemento_asignacion_id')
                  ->on('ELEMENTO_ASIGNACION')
                  ->onDelete('cascade');

            $table->text('comentario')->nullable();

            $table->timestamps();

            $table->primary(
                ['compromiso_elemento_id', 'elemento_asignacion_id'],
                'pk_cme_asignacion'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('COMPROMISO_MEJORA_ELEMENTO_ASIGNACION');
    }
};
