<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('COMPROMISO_MEJORA_ELEMENTO_ASIGNACION', function (Blueprint $table) {
            $table->unsignedBigInteger('compromiso_elemento_id');
            $table->unsignedBigInteger('elemento_asignacion_id');
            $table->string('comentario', 500)->nullable();
            $table->timestamps();
            $table->primary(['compromiso_elemento_id', 'elemento_asignacion_id']);
            $table->foreign('compromiso_elemento_id', 'cmea_compromiso_fk')
                ->references('compromiso_elemento_id')
                ->on('COMPROMISO_MEJORA_ELEMENTO')
                ->onDelete('cascade');
            $table->foreign('elemento_asignacion_id', 'cmea_elemento_asignacion_fk')
                ->references('elemento_asignacion_id')
                ->on('ELEMENTO_ASIGNACION')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('COMPROMISO_MEJORA_ELEMENTO_ASIGNACION');
    }
};